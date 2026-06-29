<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolController extends Controller
{
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
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:schools,email',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'province'      => 'nullable|string|max:100',
            'level'         => 'required|in:sd,smp,sma,smk,mixed',
            'plan_slug'     => 'required|exists:subscription_plans,slug',
            // Admin sekolah
            'admin_name'    => 'required|string|max:255',
            'admin_email'   => 'required|email|unique:users,email',
            'admin_password'=> 'required|string|min:8',
        ]);

        $plan = SubscriptionPlan::where('slug', $request->plan_slug)->firstOrFail();

        // Buat sekolah
        $school = School::create([
            'name'          => $request->name,
            'slug'          => Str::slug($request->name),
            'email'         => $request->email,
            'phone'         => $request->phone,
            'address'       => $request->address,
            'city'          => $request->city,
            'province'      => $request->province,
            'level'         => $request->level,
            'is_active'     => true,
            'trial_ends_at' => now()->addDays(14),
        ]);

        // Buat akun admin sekolah
        $admin = User::create([
            'name'                   => $request->admin_name,
            'email'                  => $request->admin_email,
            'password'               => Hash::make($request->admin_password),
            'school_id'              => $school->id,
            'subscription_plan_id'   => $plan->id,
            'role'                   => 'school_admin',
            'email_verified_at'      => now(),
            'is_active'              => true,
            'quota_reset_at'         => now()->startOfMonth()->addMonth(),
        ]);

        // Buat subscription trial
        SchoolSubscription::create([
            'school_id'              => $school->id,
            'subscription_plan_id'   => $plan->id,
            'status'                 => 'trial',
            'billing_cycle'          => 'monthly',
            'amount_paid'            => 0,
            'quota_used'             => 0,
            'starts_at'              => now(),
            'ends_at'                => now()->addDays(14),
            'quota_reset_at'         => now()->startOfMonth()->addMonth(),
        ]);

        return redirect()
            ->route('admin.schools.index')
            ->with('success', "Sekolah {$school->name} berhasil didaftarkan dengan akun admin {$admin->email}.");
    }

    public function show(School $school)
    {
        $school->load(['users', 'subscriptions.plan']);
        $teachers = $school->teachers()->withCount('questionSets')->paginate(20);

        return view('admin.schools.show', compact('school', 'teachers'));
    }

    public function toggleActive(School $school)
    {
        $school->update(['is_active' => ! $school->is_active]);

        $status = $school->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Sekolah {$school->name} berhasil {$status}.");
    }

    /**
     * Form perpanjang/upgrade subscription sekolah.
     */
    public function editSubscription(School $school)
    {
        $plans           = SubscriptionPlan::where('is_active', true)->get();
        $activeSub       = $school->subscriptions()->latest()->first();

        return view('admin.schools.subscription', compact('school', 'plans', 'activeSub'));
    }

    /**
     * Proses perpanjang atau upgrade subscription.
     */
    public function updateSubscription(Request $request, School $school)
    {
        $request->validate([
            'plan_slug'      => 'required|exists:subscription_plans,slug',
            'billing_cycle'  => 'required|in:monthly,yearly',
            'duration_months'=> 'required|integer|min:1|max:24',
            'amount_paid'    => 'nullable|integer|min:0',
            'payment_method' => 'nullable|string|max:100',
            'payment_ref'    => 'nullable|string|max:255',
        ]);

        $plan     = SubscriptionPlan::where('slug', $request->plan_slug)->firstOrFail();
        $months   = (int) $request->duration_months;
        $startsAt = now();
        $endsAt   = now()->addMonths($months);

        // Nonaktifkan subscription lama
        $school->subscriptions()->whereIn('status', ['active', 'trial'])
            ->update(['status' => 'expired']);

        // Buat subscription baru
        SchoolSubscription::create([
            'school_id'            => $school->id,
            'subscription_plan_id' => $plan->id,
            'status'               => 'active',
            'billing_cycle'        => $request->billing_cycle,
            'amount_paid'          => $request->amount_paid ?? 0,
            'payment_method'       => $request->payment_method,
            'payment_ref'          => $request->payment_ref,
            'quota_used'           => 0,
            'starts_at'            => $startsAt,
            'ends_at'              => $endsAt,
            'quota_reset_at'       => now()->startOfMonth()->addMonth(),
        ]);

        // Update subscription_plan_id semua guru di sekolah ini
        User::where('school_id', $school->id)
            ->update(['subscription_plan_id' => $plan->id]);

        return redirect()
            ->route('admin.schools.show', $school)
            ->with('success', "Subscription {$school->name} berhasil diperbarui ke paket {$plan->name} hingga {$endsAt->format('d M Y')}.");
    }

    /**
     * Reset quota semua guru di sekolah ini.
     */
    public function resetQuota(School $school)
    {
        $count = User::where('school_id', $school->id)
            ->update([
                'quota_used_this_month' => 0,
                'quota_reset_at'        => now()->startOfMonth()->addMonth(),
            ]);

        // Reset juga quota di school_subscription aktif
        $school->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->update([
                'quota_used'      => 0,
                'quota_reset_at'  => now()->startOfMonth()->addMonth(),
            ]);

        return back()->with('success', "Quota {$count} guru di {$school->name} berhasil direset.");
    }
}