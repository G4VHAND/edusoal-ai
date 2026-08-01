<?php

namespace App\Services\Dashboard;

use App\Models\QuestionSet;
use App\Models\SchoolSubscription;
use App\Models\User;

/**
 * Hitung widget & data chart untuk dashboard super admin (analytics
 * platform-wide). Terpisah dari DashboardService (yang menghitung
 * dashboard PERSONAL guru/individual) karena skop datanya beda total:
 * di sini semua query sengaja TIDAK di-filter per user.
 *
 * Catatan definisi (dikonfirmasi ke pembimbing/product owner):
 * - "Guru Aktif"          = guru yang generate soal 30 hari terakhir,
 *                           BUKAN status login (tidak ada tracking login).
 * - "Rata-rata Waktu Generate" = PERKIRAAN dari (updated_at - created_at)
 *                           soal berstatus 'completed'. Ini termasuk waktu
 *                           antri di queue, bukan murni waktu respons AI —
 *                           harus tetap dilabeli "perkiraan" di UI.
 * - "Kuota Terpakai Bulan Ini" = total generate (termasuk aksi "tambah
 *                           soal") lewat SchoolSubscription::quota_used
 *                           (guru, dikumpulkan per sekolah) + User::
 *                           quota_used_this_month (individual). Sengaja
 *                           BUKAN hitung baris QuestionSet, karena "tambah
 *                           soal" pada bank soal yang sudah ada tidak
 *                           membuat baris baru tapi tetap konsumsi quota.
 */
class AdminDashboardService
{
    private const ACTIVE_TEACHER_WINDOW_DAYS = 30;

    private const DAILY_CHART_DAYS = 14;

    private const MONTHLY_CHART_MONTHS = 6;

    private const MONTH_NAMES = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    public function superAdminOverview(): array
    {
        return [
            'widgets' => $this->widgets(),
            'dailyGenerateChart' => $this->dailyGenerateChart(),
            'monthlyGenerateChart' => $this->monthlyCounts(QuestionSet::query()),
            'userGrowthChart' => $this->monthlyCounts(User::query()),
            'schoolGrowthChart' => $this->monthlyCounts(\App\Models\School::query()),
            'providerChart' => $this->providerChart(),
            'questionTypeChart' => $this->questionTypeChart(),
        ];
    }

    /**
     * Analytics untuk dashboard SATU sekolah (school_admin). Semua query
     * di-scope ke $school->id lewat relasi user->school_id — sekolah lain
     * tidak boleh terlihat sama sekali.
     */
    public function schoolOverview(\App\Models\School $school): array
    {
        $schoolQuestions = QuestionSet::whereHas('user', fn ($q) => $q->where('school_id', $school->id));

        return [
            'generateThisMonth' => (clone $schoolQuestions)->where('created_at', '>=', now()->startOfMonth())->count(),
            'monthlyGenerateChart' => $this->monthlyCounts((clone $schoolQuestions)),
            'subjectChart' => $this->schoolSubjectChart($school),
            'questionTypeChart' => $this->schoolQuestionTypeChart($school),
            'topTeachers' => $this->schoolTopTeachers($school),
            'recentActivity' => $this->schoolRecentActivity($school),
        ];
    }

    /**
     * Feed "Aktivitas Guru Terbaru" — siapa generate apa, kapan.
     */
    private function schoolRecentActivity(\App\Models\School $school): array
    {
        return QuestionSet::whereHas('user', fn ($q) => $q->where('school_id', $school->id))
            ->with('user:id,name')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($qs) => [
                'teacher' => $qs->user->name ?? '—',
                'subject' => $qs->subject,
                'total_questions' => $qs->total_questions,
                'created_at' => $qs->created_at,
            ])
            ->all();
    }

    private function schoolSubjectChart(\App\Models\School $school): array
    {
        $counts = QuestionSet::whereHas('user', fn ($q) => $q->where('school_id', $school->id))
            ->whereNotNull('subject')
            ->selectRaw('subject, COUNT(*) as total')
            ->groupBy('subject')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('total', 'subject');

        return ['labels' => $counts->keys()->all(), 'totals' => $counts->values()->all()];
    }

    private function schoolQuestionTypeChart(\App\Models\School $school): array
    {
        $base = QuestionSet::whereHas('user', fn ($q) => $q->where('school_id', $school->id));

        return [
            'labels' => ['Pilihan Ganda', 'Essay'],
            'totals' => [
                (clone $base)->where('question_type', 'multiple_choice')->count(),
                (clone $base)->where('question_type', 'essay')->count(),
            ],
        ];
    }

    /**
     * Ranking guru paling aktif (jumlah generate) di sekolah ini, 3 teratas.
     */
    private function schoolTopTeachers(\App\Models\School $school): array
    {
        $rows = QuestionSet::whereHas('user', fn ($q) => $q->where('school_id', $school->id)->where('role', 'teacher'))
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        $names = User::whereIn('id', $rows->pluck('user_id'))->pluck('name', 'id');

        return $rows->map(fn ($row) => [
            'name' => $names[$row->user_id] ?? '—',
            'total' => (int) $row->total,
        ])->all();
    }

    private function widgets(): array
    {
        $topProvider = QuestionSet::whereNotNull('ai_provider')
            ->selectRaw('ai_provider, COUNT(*) as total')
            ->groupBy('ai_provider')
            ->orderByDesc('total')
            ->first();

        return [
            'generate_today' => QuestionSet::whereDate('created_at', now()->toDateString())->count(),
            'generate_this_month' => QuestionSet::where('created_at', '>=', now()->startOfMonth())->count(),
            'active_teachers' => $this->activeTeachers(),
            'top_provider_label' => $topProvider ? ucfirst($topProvider->ai_provider) : '—',
            'top_provider_count' => $topProvider->total ?? 0,
            'avg_generate_label' => $this->averageGenerateLabel(),
            'quota_used_this_month' => $this->quotaUsedThisMonth(),
        ];
    }

    private function activeTeachers(): int
    {
        return QuestionSet::where('created_at', '>=', now()->subDays(self::ACTIVE_TEACHER_WINDOW_DAYS))
            ->whereHas('user', fn ($q) => $q->where('role', 'teacher'))
            ->distinct()
            ->count('user_id');
    }

    /**
     * Perkiraan rata-rata waktu generate, diformat siap tampil (mis. "2m 15s").
     * Null (soal completed belum ada) ditampilkan sebagai '—' di widget().
     */
    private function averageGenerateLabel(): string
    {
        $durations = QuestionSet::where('status', 'completed')
            ->select('created_at', 'updated_at')
            ->get()
            // abs() eksplisit di sini karena diffInSeconds() TIDAK selalu
            // mengembalikan nilai absolut tergantung urutan argumen & versi
            // Carbon — tanpa ini, hasilnya bisa negatif dan merusak avg().
            ->map(fn ($qs) => abs($qs->updated_at->diffInSeconds($qs->created_at)));

        if ($durations->isEmpty()) {
            return '—';
        }

        $avgSeconds = (int) round($durations->avg());

        return $avgSeconds >= 60
            ? sprintf('%dm %ds', intdiv($avgSeconds, 60), $avgSeconds % 60)
            : "{$avgSeconds}s";
    }

    private function quotaUsedThisMonth(): int
    {
        $schoolQuota = SchoolSubscription::whereIn('status', ['active', 'trial'])->sum('quota_used');

        $individualQuota = User::where('role', 'individual')
            ->whereNull('school_id')
            ->sum('quota_used_this_month');

        return (int) ($schoolQuota + $individualQuota);
    }

    /**
     * Generate per hari, 14 hari terakhir termasuk hari ini.
     */
    private function dailyGenerateChart(): array
    {
        $from = now()->subDays(self::DAILY_CHART_DAYS - 1)->startOfDay();

        $rows = QuestionSet::where('created_at', '>=', $from)
            ->select('created_at')
            ->get()
            ->groupBy(fn ($qs) => $qs->created_at->toDateString());

        $labels = [];
        $totals = [];

        for ($i = self::DAILY_CHART_DAYS - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');
            $totals[] = $rows->get($date->toDateString())?->count() ?? 0;
        }

        return ['labels' => $labels, 'totals' => $totals];
    }

    /**
     * Hitung jumlah baris per bulan untuk N bulan terakhir, dipakai baik
     * untuk chart "Generate / bulan" (query QuestionSet) maupun
     * "Pertumbuhan Pengguna" (query User). Catatan sama seperti
     * DashboardService: ekstrak bulan di PHP, bukan groupBy+MONTH() di
     * SQL, supaya portable ke SQLite (dipakai saat testing).
     */
    private function monthlyCounts(\Illuminate\Database\Eloquent\Builder $query): array
    {
        $base = now()->startOfMonth();
        $from = $base->copy()->subMonths(self::MONTHLY_CHART_MONTHS - 1);

        $rows = $query->where('created_at', '>=', $from)
            ->select('created_at')
            ->get()
            ->groupBy(fn ($row) => $row->created_at->format('Y-m'));

        $labels = [];
        $totals = [];

        for ($i = self::MONTHLY_CHART_MONTHS - 1; $i >= 0; $i--) {
            $date = $base->copy()->subMonths($i);
            $labels[] = self::MONTH_NAMES[(int) $date->format('n')].' '.$date->format('y');
            $totals[] = $rows->get($date->format('Y-m'))?->count() ?? 0;
        }

        return ['labels' => $labels, 'totals' => $totals];
    }

    private function providerChart(): array
    {
        $counts = QuestionSet::whereNotNull('ai_provider')
            ->selectRaw('ai_provider, COUNT(*) as total')
            ->groupBy('ai_provider')
            ->pluck('total', 'ai_provider');

        return [
            'labels' => $counts->keys()->map(fn ($p) => ucfirst($p))->all(),
            'totals' => $counts->values()->all(),
        ];
    }

    private function questionTypeChart(): array
    {
        return [
            'labels' => ['Pilihan Ganda', 'Essay'],
            'totals' => [
                QuestionSet::where('question_type', 'multiple_choice')->count(),
                QuestionSet::where('question_type', 'essay')->count(),
            ],
        ];
    }
}