<?php

namespace App\Http\Controllers;

use App\Models\QuestionSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Super admin & school admin tidak generate soal sendiri (diblokir
        // role:teacher,individual di route /generate-soal), jadi dashboard
        // personal ini akan selalu kosong buat mereka + tombol "Generate
        // Soal" di dalamnya akan 403 kalau diklik. Lempar ke dashboard
        // admin mereka yang sebenarnya, supaya tidak jadi dead-end.
        if ($user->isSuperAdmin() || $user->isSchoolAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $userId = auth()->id();
        $period = $request->get('period', 'all');

        // Cache key unik per user per period
        // TTL: 5 menit — dashboard tidak perlu realtime
        $cacheKey = "dashboard:{$userId}:{$period}";
        $ttl = now()->addMinutes(5);

        $data = Cache::remember($cacheKey, $ttl, function () use ($userId, $period) {

            // ── Query 1: Semua agregasi dalam SATU query ──────────────────────
            $stats = QuestionSet::where('user_id', $userId)
                ->when($period === '7days', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
                ->when($period === '30days', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
                ->when($period === 'year', fn ($q) => $q->whereYear('created_at', now()->year))
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
            // Catatan: JANGAN groupBy+MONTH() di level SQL — MONTH() itu
            // fungsi khusus MySQL, tidak ada di SQLite (dipakai saat testing).
            // Ambil created_at mentah, ekstrak bulan di PHP (portable).
            $groupedStats = QuestionSet::where('user_id', $userId)
                ->when($period === '7days', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
                ->when($period === '30days', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
                ->when($period === 'year', fn ($q) => $q->whereYear('created_at', now()->year))
                ->select('subject', 'created_at')
                ->get()
                ->map(fn ($row) => [
                    'subject' => $row->subject,
                    'month'   => $row->created_at->month,
                ])
                ->toArray(); // ← convert ke array agar bisa di-cache dengan aman

            // ── Query 3: Latest question sets — simpan sebagai array biasa ────
            $latestQuestionSets = QuestionSet::where('user_id', $userId)
                ->when($period === '7days', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
                ->when($period === '30days', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
                ->when($period === 'year', fn ($q) => $q->whereYear('created_at', now()->year))
                ->latest()
                ->take(5)
                ->get()
                ->toArray(); // ← convert ke array

            return [
                'stats' => $stats ? $stats->toArray() : [],
                'groupedStats' => $groupedStats,
                'latestQuestionSets' => $latestQuestionSets,
            ];
        });

        // Extract dari cache — sekarang semua berupa array
        $stats = $data['stats'];
        $groupedStats = collect($data['groupedStats']);
        $latestQuestionSets = collect($data['latestQuestionSets']);

        // Transform — groupedStats sekarang berisi 1 baris per QuestionSet
        // (bukan hasil pre-aggregate SQL), jadi hitung total pakai count().
        $subjectStats = $groupedStats
            ->groupBy('subject')
            ->map(fn ($rows) => (object) ['subject' => $rows->first()['subject'], 'total' => $rows->count()])
            ->sortByDesc('total')->values();

        $topSubjects = $subjectStats->take(5);

        $monthlyActivity = $groupedStats
            ->groupBy('month')
            ->map(fn ($rows) => (object) ['month' => $rows->first()['month'], 'total' => $rows->count()])
            ->sortBy('month')->values();

        // Pre-compute label bulan & total sebagai array biasa (hindari closure di Blade)
        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $monthlyLabels = $monthlyActivity->map(fn ($row) => $monthNames[$row->month] ?? '-')->values()->all();
        $monthlyTotals = $monthlyActivity->pluck('total')->values()->all();

        $totalQuestionSets = (int) ($stats['total_question_sets'] ?? 0);
        $totalQuestions = (int) ($stats['total_questions'] ?? 0);
        $totalMultipleChoice = (int) ($stats['total_multiple_choice'] ?? 0);
        $totalEssay = (int) ($stats['total_essay'] ?? 0);
        $easyCount = (int) ($stats['easy_count'] ?? 0);
        $mediumCount = (int) ($stats['medium_count'] ?? 0);
        $hardCount = (int) ($stats['hard_count'] ?? 0);
        $aiGeneratedCount = (int) ($stats['ai_generated_count'] ?? 0);

        return view('dashboard', compact(
            'totalQuestionSets', 'totalQuestions', 'totalMultipleChoice',
            'totalEssay', 'easyCount', 'mediumCount', 'hardCount',
            'latestQuestionSets', 'aiGeneratedCount', 'subjectStats',
            'monthlyActivity', 'monthlyLabels', 'monthlyTotals', 'period', 'topSubjects'
        ));
    }
}
