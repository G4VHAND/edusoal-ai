<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\QuestionSet;
use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Dashboard\AdminDashboardService;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $analyticsService,
    ) {}

    public function index()
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return $this->superAdminDashboard();
        }

        return $this->schoolAdminDashboard($user);
    }

    /**
     * Dashboard super admin — statistik SELURUH platform.
     */
    private function superAdminDashboard()
    {
        $stats = [
            'total_schools' => School::count(),
            'total_teachers' => User::where('role', 'teacher')->count(),
            'total_individuals' => User::where('role', 'individual')->count(),
            'total_questions' => QuestionSet::count(),
            'active_subs' => School::whereHas('subscriptions', fn ($q) => $q->whereIn('status', ['active', 'trial']))->count(),
        ];

        $recentSchools = School::with('activeSubscription.plan')
            ->latest()->take(10)->get();

        $plans = SubscriptionPlan::withCount('schoolSubscriptions')->get();

        $analytics = $this->analyticsService->superAdminOverview();

        $recentAuditLogs = AuditLog::with(['user', 'school'])->latest()->take(6)->get();

        return view('admin.dashboard', compact('stats', 'recentSchools', 'plans', 'recentAuditLogs') + $analytics);
    }

    /**
     * Dashboard school admin — HANYA statistik sekolahnya sendiri.
     * Tidak boleh menyentuh data sekolah lain (nama, jumlah, distribusi
     * paket seluruh platform, dsb) — itu data bisnis internal platform,
     * bukan konsumsi customer.
     */
    private function schoolAdminDashboard(User $user)
    {
        $school = $user->school;

        abort_if(! $school, 403, 'Akun Anda tidak terhubung ke sekolah manapun.');

        $stats = [
            'total_teachers' => $school->users()->where('role', 'teacher')->count(),
            'total_questions' => QuestionSet::whereHas('user', fn ($q) => $q->where('school_id', $school->id))->count(),
            'quota_used' => $school->quotaUsed(),
            'quota_limit' => $school->quotaLimit(),
        ];

        $plan = $school->activePlan();

        $recentTeachers = $school->users()
            ->where('role', 'teacher')
            ->latest()
            ->take(5)
            ->get();

        $analytics = $this->analyticsService->schoolOverview($school);

        $recentAuditLogs = AuditLog::with('user')->where('school_id', $school->id)->latest()->take(5)->get();

        return view('admin.school-dashboard', compact('school', 'stats', 'plan', 'recentTeachers', 'recentAuditLogs') + $analytics);
    }
}