<?php

namespace Tests\Unit;

use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(array $attrs = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'name' => 'Test Plan',
            'slug' => 'test-'.uniqid(),
            'price_monthly' => 49000,
            'price_yearly' => 490000,
            'max_teachers' => 5,
            'quota_per_month' => 100,
            'max_questions_per_generate' => 20,
            'allow_image_upload' => false,
            'allow_export_word' => true,
            'allow_export_pdf' => true,
            'allow_all_providers' => true,
            'is_active' => true,
        ], $attrs));
    }

    private function makeSchool(array $attrs = []): School
    {
        return School::create(array_merge([
            'name' => 'Sekolah Test '.uniqid(),
            'email' => uniqid().'@sekolah.test',
            'level' => 'smp',
            'is_active' => true,
        ], $attrs));
    }

    private function makeSubscription(School $school, SubscriptionPlan $plan, array $attrs = []): SchoolSubscription
    {
        return SchoolSubscription::create(array_merge([
            'school_id' => $school->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount_paid' => $plan->price_monthly,
            'quota_used' => 0,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'quota_reset_at' => now()->addMonth(),
        ], $attrs));
    }

    private function makeTeacher(School $school, array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'teacher',
            'school_id' => $school->id,
            'is_active' => true,
        ], $attrs));
    }

    // ── Quota dasar per-sekolah ──────────────────────────────────────────────

    public function test_school_has_quota_from_active_subscription(): void
    {
        $plan = $this->makePlan(['quota_per_month' => 100]);
        $school = $this->makeSchool();
        $this->makeSubscription($school, $plan);

        $this->assertTrue($school->hasQuota());
        $this->assertEquals(100, $school->remainingQuota());
    }

    public function test_school_quota_decreases_after_increment(): void
    {
        $plan = $this->makePlan(['quota_per_month' => 100]);
        $school = $this->makeSchool();
        $this->makeSubscription($school, $plan, ['quota_used' => 10]);

        $school->incrementQuota();

        $this->assertEquals(89, $school->remainingQuota());
        $this->assertEquals(11, $school->quotaUsed());
    }

    public function test_school_without_quota_cannot_generate(): void
    {
        $plan = $this->makePlan(['quota_per_month' => 100]);
        $school = $this->makeSchool();
        $this->makeSubscription($school, $plan, ['quota_used' => 100]);

        $this->assertFalse($school->hasQuota());
        $this->assertEquals(0, $school->remainingQuota());
    }

    public function test_school_unlimited_plan_always_has_quota(): void
    {
        $plan = $this->makePlan(['quota_per_month' => -1]);
        $school = $this->makeSchool();
        $this->makeSubscription($school, $plan, ['quota_used' => 99999]);

        $this->assertTrue($school->hasQuota());
        $this->assertEquals(-1, $school->remainingQuota());
    }

    public function test_school_without_active_subscription_has_no_quota(): void
    {
        $school = $this->makeSchool(); // tidak ada subscription sama sekali

        $this->assertFalse($school->hasQuota());
        $this->assertEquals(0, $school->remainingQuota());
    }

    // ── Pooled: dipakai bersama semua guru di sekolah yang sama ─────────────

    public function test_quota_is_shared_across_all_teachers_in_same_school(): void
    {
        $plan = $this->makePlan(['quota_per_month' => 10]);
        $school = $this->makeSchool();
        $this->makeSubscription($school, $plan);

        $teacherA = $this->makeTeacher($school);
        $teacherB = $this->makeTeacher($school);

        $this->assertEquals(10, $teacherA->remainingQuota());
        $this->assertEquals(10, $teacherB->remainingQuota());

        // Guru A generate 3x — quota berkurang untuk KEDUA guru (shared pool).
        $teacherA->incrementQuota();
        $teacherA->incrementQuota();
        $teacherA->incrementQuota();

        $this->assertEquals(7, $teacherA->fresh()->remainingQuota());
        $this->assertEquals(7, $teacherB->fresh()->remainingQuota());
    }

    public function test_teacher_blocked_when_school_quota_exhausted_even_if_never_generated_personally(): void
    {
        $plan = $this->makePlan(['quota_per_month' => 5]);
        $school = $this->makeSchool();
        $this->makeSubscription($school, $plan, ['quota_used' => 5]);

        $teacherA = $this->makeTeacher($school); // belum pernah generate sama sekali

        $this->assertFalse($teacherA->hasQuota());
    }

    public function test_individual_user_quota_is_not_affected_by_school_pooling(): void
    {
        $individual = User::factory()->create([
            'role' => 'individual',
            'school_id' => null,
            'quota_used_this_month' => 3,
            'quota_reset_at' => now()->addMonth(),
        ]);

        // User individual tanpa sekolah tetap pakai quota per-akun sendiri.
        $this->assertEquals(7, $individual->remainingQuota()); // default free = 10
    }

    // ── Regresi bug: activeSubscription() dulu bisa bocor ke sekolah lain ───

    public function test_active_subscription_does_not_leak_across_schools(): void
    {
        $plan = $this->makePlan();

        $schoolA = $this->makeSchool(['name' => 'Sekolah A']);
        $this->makeSubscription($schoolA, $plan, ['status' => 'active']);

        // Sekolah B dibuat belakangan dengan status trial — dulu, karena bug
        // orWhere tanpa grouping, subscription trial sekolah B ini bisa
        // "ketarik" jadi activeSubscription milik sekolah A (latest() global,
        // school_id lepas dari kondisi 'trial').
        $schoolB = $this->makeSchool(['name' => 'Sekolah B']);
        $this->makeSubscription($schoolB, $plan, ['status' => 'trial']);

        $schoolA->refresh();

        $this->assertNotNull($schoolA->activeSubscription);
        $this->assertEquals($schoolA->id, $schoolA->activeSubscription->school_id);
    }

    public function test_teacher_quota_uses_correct_schools_subscription_not_another_schools(): void
    {
        $planA = $this->makePlan(['quota_per_month' => 100]);
        $planB = $this->makePlan(['quota_per_month' => 5]);

        $schoolA = $this->makeSchool(['name' => 'Sekolah A']);
        $this->makeSubscription($schoolA, $planA, ['status' => 'active']);

        $schoolB = $this->makeSchool(['name' => 'Sekolah B']);
        $this->makeSubscription($schoolB, $planB, ['status' => 'trial']);

        $teacherA = $this->makeTeacher($schoolA);

        // Guru sekolah A harus dapat quota 100 dari plan sekolahnya sendiri,
        // BUKAN 5 dari subscription trial sekolah B.
        $this->assertEquals(100, $teacherA->remainingQuota());
    }

    // ── Null-safety: atribut quota_used bisa belum "ter-hydrate" di memori ──
    // (School::quotaUsed() dkk selalu fresh-query, jadi kebal dari isu ini —
    // yang perlu diuji adalah SchoolSubscription-nya langsung.)

    public function test_subscription_handles_unhydrated_quota_used_attribute(): void
    {
        $plan   = $this->makePlan(['quota_per_month' => 10]);
        $school = $this->makeSchool();

        // Sengaja TIDAK pass 'quota_used' — persis seperti objek hasil
        // create() yang belum di-refresh dari DB.
        $subscription = SchoolSubscription::create([
            'school_id'            => $school->id,
            'subscription_plan_id' => $plan->id,
            'status'               => 'active',
            'billing_cycle'        => 'monthly',
            'amount_paid'          => $plan->price_monthly,
            'starts_at'            => now(),
            'ends_at'              => now()->addMonth(),
            'quota_reset_at'       => now()->addMonth(),
        ]);

        $this->assertTrue($subscription->hasQuota());
        $this->assertEquals(10, $subscription->remainingQuota());
    }

    public function test_subscription_increment_quota_works_when_attribute_not_hydrated(): void
    {
        $plan   = $this->makePlan(['quota_per_month' => 10]);
        $school = $this->makeSchool();

        $subscription = SchoolSubscription::create([
            'school_id'            => $school->id,
            'subscription_plan_id' => $plan->id,
            'status'               => 'active',
            'billing_cycle'        => 'monthly',
            'amount_paid'          => $plan->price_monthly,
            'starts_at'            => now(),
            'ends_at'              => now()->addMonth(),
            'quota_reset_at'       => now()->addMonth(),
        ]);

        $subscription->incrementQuota();
        $subscription->refresh();

        // Bukan tetap null (SQL NULL+1=NULL) — harus jadi 1.
        $this->assertEquals(1, $subscription->quota_used);
    }
}
