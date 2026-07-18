<?php

namespace App\Services\Dashboard;

use App\Models\QuestionSet;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Hitung semua data untuk dashboard guru/individual: statistik agregat,
 * grafik mata pelajaran & aktivitas bulanan, dan 5 bank soal terbaru.
 *
 * Dipisah dari DashboardController supaya method index() tidak jadi 1
 * blok raksasa — tiap tahap (query, cache, transform-untuk-chart) sekarang
 * punya nama method sendiri yang menjelaskan apa yang sedang dikerjakan.
 */
class DashboardService
{
    private const CACHE_TTL_MINUTES = 5;

    private const MONTH_NAMES = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    /**
     * @return array Data siap pakai untuk view('dashboard', ...) — sudah
     *                termasuk key 'period' supaya controller tinggal
     *                return view('dashboard', $this->forUser(...)).
     */
    public function forUser(User $user, string $period): array
    {
        $raw = $this->fetchCached($user->id, $period);

        $stats = $raw['stats'];
        $groupedStats = collect($raw['groupedStats']);
        $latestQuestionSets = collect($raw['latestQuestionSets']);

        $subjectStats = $this->subjectStats($groupedStats);
        $monthlyActivity = $this->monthlyActivity($groupedStats);

        return [
            'totalQuestionSets' => (int) ($stats['total_question_sets'] ?? 0),
            'totalQuestions' => (int) ($stats['total_questions'] ?? 0),
            'totalMultipleChoice' => (int) ($stats['total_multiple_choice'] ?? 0),
            'totalEssay' => (int) ($stats['total_essay'] ?? 0),
            'easyCount' => (int) ($stats['easy_count'] ?? 0),
            'mediumCount' => (int) ($stats['medium_count'] ?? 0),
            'hardCount' => (int) ($stats['hard_count'] ?? 0),
            'aiGeneratedCount' => (int) ($stats['ai_generated_count'] ?? 0),
            'latestQuestionSets' => $latestQuestionSets,
            'subjectStats' => $subjectStats,
            'topSubjects' => $subjectStats->take(5),
            'monthlyActivity' => $monthlyActivity,
            'monthlyLabels' => $this->monthlyLabels($monthlyActivity),
            'monthlyTotals' => $monthlyActivity->pluck('total')->values()->all(),
            'period' => $period,
        ];
    }

    /**
     * 3 query (agregat, data chart, latest 5) di-cache jadi satu unit per
     * user per period — TTL 5 menit, dashboard tidak perlu realtime.
     * Semua hasil disimpan sebagai array biasa (bukan Eloquent Collection)
     * supaya aman di-cache (lihat catatan di masing-masing query di bawah).
     */
    private function fetchCached(int $userId, string $period): array
    {
        $cacheKey = "dashboard:{$userId}:{$period}";
        $ttl = now()->addMinutes(self::CACHE_TTL_MINUTES);

        return Cache::remember($cacheKey, $ttl, function () use ($userId, $period) {
            return [
                'stats' => $this->aggregateStats($userId, $period),
                'groupedStats' => $this->chartRows($userId, $period),
                'latestQuestionSets' => $this->latestQuestionSets($userId, $period),
            ];
        });
    }

    /**
     * Semua agregasi dalam SATU query (COUNT, SUM per kategori) — jauh
     * lebih murah daripada 8 query terpisah.
     */
    private function aggregateStats(int $userId, string $period): array
    {
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

        return $stats ? $stats->toArray() : [];
    }

    /**
     * Data mentah untuk chart (subject + bulan). Catatan: JANGAN
     * groupBy+MONTH() di level SQL — MONTH() itu fungsi khusus MySQL,
     * tidak ada di SQLite (dipakai saat testing). Ambil created_at mentah,
     * ekstrak bulan di PHP (portable).
     */
    private function chartRows(int $userId, string $period): array
    {
        return QuestionSet::where('user_id', $userId)
            ->when($period === '7days', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
            ->when($period === '30days', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->when($period === 'year', fn ($q) => $q->whereYear('created_at', now()->year))
            ->select('subject', 'created_at')
            ->get()
            ->map(fn ($row) => [
                'subject' => $row->subject,
                'month' => $row->created_at->month,
            ])
            ->toArray();
    }

    private function latestQuestionSets(int $userId, string $period): array
    {
        return QuestionSet::where('user_id', $userId)
            ->when($period === '7days', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
            ->when($period === '30days', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->when($period === 'year', fn ($q) => $q->whereYear('created_at', now()->year))
            ->latest()
            ->take(5)
            ->get()
            ->toArray();
    }

    /**
     * groupedStats sekarang berisi 1 baris per QuestionSet (bukan hasil
     * pre-aggregate SQL), jadi hitung total per subject pakai count().
     */
    private function subjectStats($groupedStats)
    {
        return $groupedStats
            ->groupBy('subject')
            ->map(fn ($rows) => (object) ['subject' => $rows->first()['subject'], 'total' => $rows->count()])
            ->sortByDesc('total')->values();
    }

    private function monthlyActivity($groupedStats)
    {
        return $groupedStats
            ->groupBy('month')
            ->map(fn ($rows) => (object) ['month' => $rows->first()['month'], 'total' => $rows->count()])
            ->sortBy('month')->values();
    }

    /**
     * Label bulan sebagai array biasa (bukan closure di Blade), sesuai
     * urutan $monthlyActivity.
     */
    private function monthlyLabels($monthlyActivity): array
    {
        return $monthlyActivity
            ->map(fn ($row) => self::MONTH_NAMES[$row->month] ?? '-')
            ->values()->all();
    }
}
