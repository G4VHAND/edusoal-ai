<?php

namespace App\Services\School;

use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Business logic untuk manajemen sekolah oleh super_admin: pendaftaran
 * sekolah baru, perpanjangan/upgrade subscription, dan reset quota massal.
 *
 * Sebelumnya logic ini ada langsung di SchoolController tanpa audit log
 * sama sekali — beda dengan modul lain (Bank Soal, Template, dll) yang
 * konsisten di-log. Sekarang setiap aksi di sini tercatat lewat
 * AuditLogService, mengikuti taksonomi module 'Sekolah' yang sudah dipakai
 * SchoolLetterheadController.
 */
class SchoolService
{
    /**
     * Daftarkan sekolah baru sekaligus akun admin sekolahnya & subscription
     * trial 14 hari.
     *
     * @param  array  $validated  Hasil validasi dari SchoolController::store()
     * @return array{school: School, admin: User}
     */
    public function create(array $validated): array
    {
        $plan = SubscriptionPlan::where('slug', $validated['plan_slug'])->firstOrFail();

        $school = School::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'level' => $validated['level'],
            'is_active' => true,
            'trial_ends_at' => now()->addDays(14),
        ]);

        $admin = User::create([
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => Hash::make($validated['admin_password']),
            'school_id' => $school->id,
            'subscription_plan_id' => $plan->id,
            'role' => 'school_admin',
            'email_verified_at' => now(),
            'is_active' => true,
            'quota_reset_at' => now()->startOfMonth()->addMonth(),
        ]);

        SchoolSubscription::create([
            'school_id' => $school->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'trial',
            'billing_cycle' => 'monthly',
            'amount_paid' => 0,
            'quota_used' => 0,
            'starts_at' => now(),
            'ends_at' => now()->addDays(14),
            'quota_reset_at' => now()->startOfMonth()->addMonth(),
        ]);

        AuditLogService::log(
            module: 'Sekolah',
            event: 'create',
            description: "Mendaftarkan sekolah '{$school->name}' dengan admin '{$admin->email}' (paket {$plan->name}, trial 14 hari)",
            properties: [
                'school_id' => $school->id,
                'admin_user_id' => $admin->id,
                'plan_slug' => $plan->slug,
            ]
        );

        return ['school' => $school, 'admin' => $admin];
    }

    public function toggleActive(School $school): School
    {
        $school->update(['is_active' => ! $school->is_active]);

        AuditLogService::log(
            module: 'Sekolah',
            event: 'toggle_active',
            description: "Sekolah '{$school->name}' ".($school->is_active ? 'diaktifkan' : 'dinonaktifkan'),
            properties: ['school_id' => $school->id, 'is_active' => $school->is_active]
        );

        return $school;
    }

    /**
     * Perpanjang atau upgrade subscription sekolah. Subscription lama
     * (active/trial) otomatis di-expire, subscription baru dibuat dari nol,
     * dan subscription_plan_id semua guru di sekolah ini ikut diperbarui.
     */
    public function updateSubscription(School $school, array $validated): SchoolSubscription
    {
        $plan = SubscriptionPlan::where('slug', $validated['plan_slug'])->firstOrFail();
        $months = (int) $validated['duration_months'];
        $startsAt = now();
        $endsAt = now()->addMonths($months);

        $previousPlan = $school->activeSubscription?->plan?->name;

        // Nonaktifkan subscription lama
        $school->subscriptions()->whereIn('status', ['active', 'trial'])
            ->update(['status' => 'expired']);

        $subscription = SchoolSubscription::create([
            'school_id' => $school->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => $validated['billing_cycle'],
            'amount_paid' => $validated['amount_paid'] ?? 0,
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_ref' => $validated['payment_ref'] ?? null,
            'quota_used' => 0,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'quota_reset_at' => now()->startOfMonth()->addMonth(),
        ]);

        // Update subscription_plan_id semua guru di sekolah ini
        User::where('school_id', $school->id)
            ->update(['subscription_plan_id' => $plan->id]);

        AuditLogService::log(
            module: 'Sekolah',
            event: 'update_subscription',
            description: "Memperbarui subscription '{$school->name}' ke paket {$plan->name} hingga {$endsAt->format('d M Y')}"
                .($previousPlan ? " (sebelumnya: {$previousPlan})" : ''),
            properties: [
                'school_id' => $school->id,
                'subscription_id' => $subscription->id,
                'previous_plan' => $previousPlan,
                'new_plan' => $plan->name,
                'billing_cycle' => $validated['billing_cycle'],
                'duration_months' => $months,
                'amount_paid' => $validated['amount_paid'] ?? 0,
            ]
        );

        return $subscription;
    }

    /**
     * Reset quota semua guru di sekolah ini + quota di subscription aktif.
     *
     * @return int Jumlah guru yang quota-nya direset
     */
    public function resetQuota(School $school): int
    {
        $count = User::where('school_id', $school->id)
            ->update([
                'quota_used_this_month' => 0,
                'quota_reset_at' => now()->startOfMonth()->addMonth(),
            ]);

        $school->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->update([
                'quota_used' => 0,
                'quota_reset_at' => now()->startOfMonth()->addMonth(),
            ]);

        AuditLogService::log(
            module: 'Sekolah',
            event: 'reset_quota',
            description: "Reset quota {$count} guru di sekolah '{$school->name}'",
            properties: ['school_id' => $school->id, 'teacher_count' => $count]
        );

        return $count;
    }
}
