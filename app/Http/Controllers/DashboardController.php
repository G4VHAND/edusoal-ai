<?php

namespace App\Http\Controllers;

use App\Models\QuestionSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $period = $request->get('period', 'all');

        // Cache key unik per user per period
        // TTL: 5 menit — dashboard tidak perlu realtime
        $cacheKey = "dashboard:{$userId}:{$period}";
        $ttl      = now()->addMinutes(5);

        $data = Cache::remember($cacheKey, $ttl, function () use ($userId, $period) {

            // ── Query 1: Semua agregasi dalam SATU query ──────────────────────
            $stats = QuestionSet::where('user_id', $userId)
                ->when($period === '7days',  fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
                ->when($period === '30days', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
                ->when($period === 'year',   fn ($q) => $q->whereYear('created_at', now()->year))
                ->selectRaw("
                    COUNT(*)                                                AS total_question_sets,
                    SUM(CASE WHEN question_type = 'multiple_choice' THEN 1 ELSE 0 END) AS total_multiple_choice,
                    SUM(CASE WHEN question_type = 'essay'           THEN 1 ELSE 0 END) AS total_essay,
                    SUM(CASE WHEN difficulty   = 'mudah'            THEN 1 ELSE 0 END) AS easy_count,
                    SUM(CASE WHEN difficulty   = 'sedang'           THEN 1 ELSE 0 END) AS medium_count,
                    SUM(CASE WHEN difficulty   = 'sulit'            THEN 1 ELSE 0 END) AS hard_count,
                    SUM(CASE WHEN is_ai_generated = 1               THEN 1 ELSE 0 END) AS ai_generated_count,
                    SUM(CASE WHEN ai_provider  = 'gemini'           THEN 1 ELSE 0 END) AS gemini_count,
                    SUM(CASE WHEN ai_provider  = 'groq'             THEN 1 ELSE 0 END) AS groq_count,
                                        SUM(total_questions)                                    AS total_questions
                ")
                ->first();

            // ── Query 2: Data untuk chart — simpan sebagai array biasa ────────
            $groupedStats = QuestionSet::where('user_id', $userId)
                ->when($period === '7days',  fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
                ->when($period === '30days', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
                ->when($period === 'year',   fn ($q) => $q->whereYear('created_at', now()->year))
                ->selectRaw("subject, ai_provider, MONTH(created_at) AS month, COUNT(*) AS total")
                ->groupBy('subject', 'ai_provider', 'month')
                ->orderBy('month')
                ->get()
                ->toArray(); // ← convert ke array agar bisa di-cache dengan aman

            // ── Query 3: Latest question sets — simpan sebagai array biasa ────
            $latestQuestionSets = QuestionSet::where('user_id', $userId)
                ->when($period === '7days',  fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
                ->when($period === '30days', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
                ->when($period === 'year',   fn ($q) => $q->whereYear('created_at', now()->year))
                ->latest()
                ->take(5)
                ->get()
                ->toArray(); // ← convert ke array

            return [
                'stats'              => $stats ? $stats->toArray() : [],
                'groupedStats'       => $groupedStats,
                'latestQuestionSets' => $latestQuestionSets,
            ];
        });

        // Extract dari cache — sekarang semua berupa array
        $stats              = $data['stats'];
        $groupedStats       = collect($data['groupedStats']);
        $latestQuestionSets = collect($data['latestQuestionSets']);

        // Transform
        $subjectStats = $groupedStats
            ->groupBy('subject')
            ->map(fn ($rows) => (object)['subject' => $rows->first()['subject'], 'total' => $rows->sum('total')])
            ->sortByDesc('total')->values();

        $topSubjects = $subjectStats->take(5);

        $providerStats = $groupedStats
            ->filter(fn ($r) => ! empty($r['ai_provider']))
            ->groupBy('ai_provider')
            ->map(fn ($rows) => (object)['ai_provider' => $rows->first()['ai_provider'], 'total' => $rows->sum('total')])
            ->sortByDesc('total')->values();

        $providerLabels = $providerStats->pluck('ai_provider')->map(fn ($p) => match ($p) {
            'gemini'   => 'Google Gemini',
            'groq'     => 'Groq',
            default    => ucfirst($p),
        });

        $favoriteProvider = match ($providerStats->first()?->ai_provider) {
            'gemini'   => 'Google Gemini',
            'groq'     => 'Groq',
            default    => '-',
        };

        $monthlyActivity = $groupedStats
            ->groupBy('month')
            ->map(fn ($rows) => (object)['month' => $rows->first()['month'], 'total' => $rows->sum('total')])
            ->sortBy('month')->values();

        // Pre-compute label bulan & total sebagai array biasa (hindari closure di Blade)
        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $monthlyLabels = $monthlyActivity->map(fn ($row) => $monthNames[$row->month] ?? '-')->values()->all();
        $monthlyTotals = $monthlyActivity->pluck('total')->values()->all();

        $totalQuestionSets   = (int) ($stats['total_question_sets']   ?? 0);
        $totalQuestions      = (int) ($stats['total_questions']        ?? 0);
        $totalMultipleChoice = (int) ($stats['total_multiple_choice']  ?? 0);
        $totalEssay          = (int) ($stats['total_essay']            ?? 0);
        $easyCount           = (int) ($stats['easy_count']             ?? 0);
        $mediumCount         = (int) ($stats['medium_count']           ?? 0);
        $hardCount           = (int) ($stats['hard_count']             ?? 0);
        $aiGeneratedCount    = (int) ($stats['ai_generated_count']     ?? 0);

        return view('dashboard', compact(
            'totalQuestionSets', 'totalQuestions', 'totalMultipleChoice',
            'totalEssay', 'easyCount', 'mediumCount', 'hardCount',
            'latestQuestionSets', 'aiGeneratedCount', 'subjectStats',
            'monthlyActivity', 'monthlyLabels', 'monthlyTotals', 'period', 'topSubjects'
        ));
    }
}