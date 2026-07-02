<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'school_id', 'subscription_plan_id', 'role',
        'quota_used_this_month', 'quota_reset_at',
        'subscription_ends_at', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'quota_reset_at'       => 'datetime',
            'subscription_ends_at' => 'datetime',
            'is_active'            => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function questionSets()
    {
        return $this->hasMany(QuestionSet::class);
    }

    public function documentTemplates()
    {
        return $this->hasMany(DocumentTemplate::class);
    }

    // ── Role helpers ──────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool   { return $this->role === 'super_admin'; }
    public function isSchoolAdmin(): bool  { return $this->role === 'school_admin'; }
    public function isTeacher(): bool      { return $this->role === 'teacher'; }
    public function isIndividual(): bool   { return $this->role === 'individual'; }
    public function hasSchool(): bool      { return $this->school_id !== null; }

    // ── Quota helpers ─────────────────────────────────────────────────────────

    /**
     * Cek apakah user masih punya quota generate bulan ini.
     */
    public function hasQuota(): bool
    {
        $this->resetQuotaIfNeeded();

        $plan = $this->subscriptionPlan;
        if (! $plan) {
            // Default: free plan — 10 generate per bulan
            return $this->quota_used_this_month < 10;
        }

        if ($plan->quota_per_month === -1) return true; // unlimited

        return $this->quota_used_this_month < $plan->quota_per_month;
    }

    /**
     * Sisa quota bulan ini.
     */
    public function remainingQuota(): int
    {
        $this->resetQuotaIfNeeded();

        $plan  = $this->subscriptionPlan;
        $limit = $plan ? $plan->quota_per_month : 10;

        if ($limit === -1) return -1; // unlimited

        return max(0, $limit - $this->quota_used_this_month);
    }

    /**
     * Tambah 1 quota usage setelah generate soal.
     */
    public function incrementQuota(): void
    {
        $this->resetQuotaIfNeeded();
        $this->increment('quota_used_this_month');
    }

    /**
     * Reset quota jika sudah ganti bulan.
     */
    private function resetQuotaIfNeeded(): void
    {
        if (! $this->quota_reset_at || $this->quota_reset_at->isPast()) {
            $this->update([
                'quota_used_this_month' => 0,
                'quota_reset_at'        => now()->startOfMonth()->addMonth(),
            ]);
        }
    }

    /**
     * Cek apakah plan user mengizinkan fitur tertentu.
     */
    public function canUseImageUpload(): bool
    {
        return $this->subscriptionPlan?->allow_image_upload ?? false;
    }

    public function canExportWord(): bool
    {
        return $this->subscriptionPlan?->allow_export_word ?? false;
    }

    public function canUseAllProviders(): bool
    {
        return $this->subscriptionPlan?->allow_all_providers ?? false;
    }
}