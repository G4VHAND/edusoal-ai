<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Super Admin — kelola user individual yang daftar sendiri.
 */
class IndividualUserController extends Controller
{
    public function index()
    {
        $search = request('search');
        $status = request('status');

        $users = User::where('role', 'individual')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->withCount('questionSets')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => User::where('role', 'individual')->count(),
            'active' => User::where('role', 'individual')->where('is_active', true)->count(),
            'verified' => User::where('role', 'individual')->whereNotNull('email_verified_at')->count(),
        ];

        return view('admin.individuals.index', compact('users', 'stats', 'search', 'status'));
    }

    public function show(User $user)
    {
        abort_if($user->role !== 'individual', 404);

        $user->loadCount('questionSets');
        $user->load('subscriptionPlan');
        $questionSets = $user->questionSets()->latest()->paginate(10);

        return view('admin.individuals.show', compact('user', 'questionSets'));
    }

    public function updatePlan(Request $request, User $user)
    {
        abort_if($user->role !== 'individual', 404);

        $request->validate([
            'plan_slug' => 'required|exists:subscription_plans,slug',
        ]);

        $plan = SubscriptionPlan::where('slug', $request->plan_slug)->firstOrFail();

        $user->update([
            'subscription_plan_id' => $plan->id,
            'quota_used_this_month' => 0,
            'quota_reset_at' => now()->startOfMonth()->addMonth(),
        ]);

        return back()->with('success', "Paket {$user->name} berhasil diubah ke {$plan->name}.");
    }

    public function resetQuota(User $user)
    {
        abort_if($user->role !== 'individual', 404);

        $user->update([
            'quota_used_this_month' => 0,
            'quota_reset_at' => now()->startOfMonth()->addMonth(),
        ]);

        return back()->with('success', "Quota {$user->name} berhasil direset.");
    }

    public function toggleActive(User $user)
    {
        abort_if($user->role !== 'individual', 404);

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun {$user->name} berhasil {$status}.");
    }

    public function destroy(User $user)
    {
        abort_if($user->role !== 'individual', 404);

        $user->delete();

        return redirect()
            ->route('admin.individuals.index')
            ->with('success', "Akun {$user->name} berhasil dihapus.");
    }
}
