<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSubscription extends Model
{
    protected $fillable = [
        'school_id', 'subscription_plan_id', 'status',
        'billing_cycle', 'amount_paid', 'payment_method',
        'payment_ref', 'quota_used', 'starts_at', 'ends_at',
        'quota_reset_at',
    ];

    protected $casts = [
        'starts_at'       => 'datetime',
        'ends_at'         => 'datetime',
        'quota_reset_at'  => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial'])
            && $this->ends_at->isFuture();
    }

    public function remainingQuota(): int
    {
        $limit = $this->plan->quota_per_month ?? 0;
        if ($limit === -1) return -1; // unlimited
        return max(0, $limit - $this->quota_used);
    }
}
