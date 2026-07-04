<?php

namespace App\Models;

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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'quota_reset_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'is_active' => 'boolean',
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

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isSchoolAdmin(): bool
    {
        return $this->role === 'school_admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isIndividual(): bool
    {
        return $this->role === 'individual';
    }

    public function hasSchool(): bool
    {
        return $this->school_id !== null;
    }

    // ── Quota helpers ─────────────────────────────────────────────────────────

    /**
     * Cek apakah user masih punya quota generate bulan ini.
     *
     * Guru/school_admin: quota DIPAKAI BERSAMA satu sekolah (pooled),
     * sesuai paket sekolahnya — bukan quota per akun masing-masing.
     * Individual (tanpa sekolah): tetap quota per akun sendiri.
     */
    public function hasQuota(): bool
    {
        if ($this->hasSchool()) {
            return $this->school?->hasQuota() ?? false;
        }

        $this->resetQuotaIfNeeded();

        $plan = $this->subscriptionPlan;
        if (! $plan) {
            // Default: free plan — 10 generate per bulan
            return $this->quota_used_this_month < 10;
        }

        if ($plan->quota_per_month === -1) {
            return true;
        } // unlimited

        return $this->quota_used_this_month < $plan->quota_per_month;
    }

    /**
     * Sisa quota bulan ini (pooled sekolah untuk guru/admin sekolah).
     */
    public function remainingQuota(): int
    {
        if ($this->hasSchool()) {
            return $this->school?->remainingQuota() ?? 0;
        }

        $this->resetQuotaIfNeeded();

        $plan = $this->subscriptionPlan;
        $limit = $plan ? $plan->quota_per_month : 10;

        if ($limit === -1) {
            return -1;
        } // unlimited

        return max(0, $limit - $this->quota_used_this_month);
    }

    /**
     * Jumlah quota yang sudah dipakai bulan ini (pooled sekolah untuk
     * guru/admin sekolah). Dipakai untuk tampilan, bukan gating.
     */
    public function quotaUsed(): int
    {
        if ($this->hasSchool()) {
            return $this->school?->quotaUsed() ?? 0;
        }

        $this->resetQuotaIfNeeded();

        return $this->quota_used_this_month;
    }

    /**
     * Batas quota bulanan yang berlaku untuk user ini (pooled sekolah
     * untuk guru/admin sekolah).
     */
    public function quotaLimit(): int
    {
        if ($this->hasSchool()) {
            return $this->school?->quotaLimit() ?? 0;
        }

        return $this->subscriptionPlan->quota_per_month ?? 10;
    }

    /**
     * Tambah 1 quota usage setelah generate soal (pooled sekolah untuk
     * guru/admin sekolah).
     */
    public function incrementQuota(): void
    {
        if ($this->hasSchool()) {
            $this->school?->incrementQuota();

            return;
        }

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
                'quota_reset_at' => now()->startOfMonth()->addMonth(),
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
