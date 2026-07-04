<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionSet;
use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_schools' => School::count(),
            'total_teachers' => User::where('role', 'teacher')->count(),
            'total_individuals' => User::where('role', 'individual')->count(),
            'total_questions' => QuestionSet::count(),
            'active_subs' => School::whereHas('subscriptions', fn ($q) => $q->whereIn('status', ['active', 'trial'])
            )->count(),
        ];

        $recentSchools = School::with('activeSubscription.plan')
            ->latest()->take(10)->get();

        $plans = SubscriptionPlan::withCount('schoolSubscriptions')->get();

        return view('admin.dashboard', compact('stats', 'recentSchools', 'plans'));
    }
}
