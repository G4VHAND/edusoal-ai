<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;

class SchoolSubscriptionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $school = $user->school;

        abort_if(! $school, 403, 'Akun Anda tidak terhubung ke sekolah manapun.');

        $subscription = $school->activeSubscription()->with('plan')->first();
        $plan = $subscription?->plan;

        $teacherCount = $school->teachers()->count();

        $quotaUsed = $subscription?->quota_used ?? 0;
        $quotaLimit = $plan?->quota_per_month ?? 0;
        $isUnlimitedQuota = $plan?->isUnlimitedQuota() ?? false;

        $history = $school->subscriptions()
            ->with('plan')
            ->latest('starts_at')
            ->get();

        $availablePlans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price_monthly')
            ->get();

        return view('admin.subscription.index', compact(
            'school', 'subscription', 'plan', 'teacherCount',
            'quotaUsed', 'quotaLimit', 'isUnlimitedQuota',
            'history', 'availablePlans'
        ));
    }
}
