<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'email', 'phone',
        'address', 'city', 'province', 'level',
        'logo', 'is_active', 'trial_ends_at',
        'headmaster_name', 'headmaster_nip',
        'letterhead_address', 'show_letterhead_on_export',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'show_letterhead_on_export' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (School $school) {
            if (empty($school->slug)) {
                $school->slug = Str::slug($school->name);
            }
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function documentTemplates()
    {
        return $this->hasMany(DocumentTemplate::class);
    }

    public function teachers()
    {
        return $this->hasMany(User::class)->where('role', 'teacher');
    }

    public function admins()
    {
        return $this->hasMany(User::class)->where('role', 'school_admin');
    }

    public function subscriptions()
    {
        return $this->hasMany(SchoolSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(SchoolSubscription::class)
            ->whereIn('status', ['active', 'trial'])
            ->latest();
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    // ── Quota (pooled per-sekolah, dipakai bersama semua guru) ──────────────

    /**
     * Ambil subscription aktif langsung dari DB (bukan relasi ter-cache),
     * supaya selalu up to date terutama saat dipanggil dari Job/queue.
     */
    private function freshActiveSubscription(): ?SchoolSubscription
    {
        return $this->activeSubscription()->with('plan')->first();
    }

    public function activePlan(): ?SubscriptionPlan
    {
        return $this->freshActiveSubscription()?->plan;
    }

    public function hasQuota(): bool
    {
        return $this->freshActiveSubscription()?->hasQuota() ?? false;
    }

    public function remainingQuota(): int
    {
        return $this->freshActiveSubscription()?->remainingQuota() ?? 0;
    }

    public function quotaUsed(): int
    {
        return $this->freshActiveSubscription()?->quota_used ?? 0;
    }

    public function quotaLimit(): int
    {
        return $this->freshActiveSubscription()?->plan?->quota_per_month ?? 0;
    }

    public function incrementQuota(): void
    {
        $this->freshActiveSubscription()?->incrementQuota();
    }
}
