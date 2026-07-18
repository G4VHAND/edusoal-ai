<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Services\School\SchoolService;
use Illuminate\Http\Request;

/**
 * Controller ini sengaja tipis — semua business logic (create school+admin,
 * subscription, reset quota, audit log) ada di SchoolService. Tanggung
 * jawab controller cuma: validasi request, panggil service, redirect.
 */
class SchoolController extends Controller
{
    public function __construct(
        private readonly SchoolService $service,
    ) {}

    public function index()
    {
        $schools = School::withCount('users')
            ->with('activeSubscription.plan')
            ->latest()
            ->paginate(20);

        return view('admin.schools.index', compact('schools'));
    }

    public function create()
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return view('admin.schools.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:schools,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'level' => 'required|in:sd,smp,sma,smk,mixed',
            'plan_slug' => 'required|exists:subscription_plans,slug',
            // Admin sekolah
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);

        $result = $this->service->create($validated);

        return redirect()
            ->route('admin.schools.index')
            ->with('success', "Sekolah {$result['school']->name} berhasil didaftarkan dengan akun admin {$result['admin']->email}.");
    }

    public function show(School $school)
    {
        $school->load(['users', 'subscriptions.plan']);
        $teachers = $school->teachers()->withCount('questionSets')->paginate(20);

        return view('admin.schools.show', compact('school', 'teachers'));
    }

    public function toggleActive(School $school)
    {
        $school = $this->service->toggleActive($school);

        $status = $school->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Sekolah {$school->name} berhasil {$status}.");
    }

    /**
     * Form perpanjang/upgrade subscription sekolah.
     */
    public function editSubscription(School $school)
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();
        $activeSub = $school->subscriptions()->latest()->first();

        return view('admin.schools.subscription', compact('school', 'plans', 'activeSub'));
    }

    /**
     * Proses perpanjang atau upgrade subscription.
     */
    public function updateSubscription(Request $request, School $school)
    {
        $validated = $request->validate([
            'plan_slug' => 'required|exists:subscription_plans,slug',
            'billing_cycle' => 'required|in:monthly,yearly',
            'duration_months' => 'required|integer|min:1|max:24',
            'amount_paid' => 'nullable|integer|min:0',
            'payment_method' => 'nullable|string|max:100',
            'payment_ref' => 'nullable|string|max:255',
        ]);

        $subscription = $this->service->updateSubscription($school, $validated);
        $subscription->load('plan');

        return redirect()
            ->route('admin.schools.show', $school)
            ->with('success', "Subscription {$school->name} berhasil diperbarui ke paket {$subscription->plan->name} hingga {$subscription->ends_at->format('d M Y')}.");
    }

    /**
     * Reset quota semua guru di sekolah ini.
     */
    public function resetQuota(School $school)
    {
        $count = $this->service->resetQuota($school);

        return back()->with('success', "Quota {$count} guru di {$school->name} berhasil direset.");
    }
}
