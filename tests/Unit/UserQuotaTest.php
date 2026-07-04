<?php

namespace Tests\Unit;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'individual',
            'quota_used_this_month' => 0,
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ], $attrs));
    }

    private function makePlan(array $attrs = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'name' => 'Test Plan',
            'slug' => 'test-'.uniqid(),
            'price_monthly' => 0,
            'price_yearly' => 0,
            'max_teachers' => 5,
            'quota_per_month' => 10,
            'max_questions_per_generate' => 10,
            'allow_image_upload' => false,
            'allow_export_word' => false,
            'allow_export_pdf' => true,
            'allow_all_providers' => false,
            'is_active' => true,
        ], $attrs));
    }

    public function test_user_without_plan_has_default_quota_10(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($user->hasQuota());
        $this->assertEquals(10, $user->remainingQuota());
    }

    public function test_user_quota_decreases_after_increment(): void
    {
        $user = $this->makeUser(['quota_used_this_month' => 5]);

        $user->incrementQuota();
        $user->refresh();

        $this->assertEquals(6, $user->quota_used_this_month);
    }

    public function test_user_without_quota_cannot_generate(): void
    {
        $user = $this->makeUser(['quota_used_this_month' => 10]);

        $this->assertFalse($user->hasQuota());
        $this->assertEquals(0, $user->remainingQuota());
    }

    public function test_unlimited_plan_always_has_quota(): void
    {
        $plan = $this->makePlan(['quota_per_month' => -1]);
        $user = $this->makeUser([
            'subscription_plan_id' => $plan->id,
            'quota_used_this_month' => 9999,
        ]);

        $this->assertTrue($user->hasQuota());
        $this->assertEquals(-1, $user->remainingQuota());
    }

    public function test_quota_resets_when_past_reset_date(): void
    {
        $user = $this->makeUser([
            'quota_used_this_month' => 8,
            'quota_reset_at' => now()->subDay(), // sudah lewat
        ]);

        // hasQuota() harus trigger reset
        $this->assertTrue($user->hasQuota());
        $user->refresh();
        $this->assertEquals(0, $user->quota_used_this_month);
    }

    public function test_role_helpers_return_correct_boolean(): void
    {
        $superAdmin = $this->makeUser(['role' => 'super_admin']);
        $schoolAdmin = $this->makeUser(['role' => 'school_admin']);
        $teacher = $this->makeUser(['role' => 'teacher']);
        $individual = $this->makeUser(['role' => 'individual']);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($superAdmin->isTeacher());

        $this->assertTrue($schoolAdmin->isSchoolAdmin());
        $this->assertFalse($schoolAdmin->isSuperAdmin());

        $this->assertTrue($teacher->isTeacher());
        $this->assertTrue($individual->isIndividual());
    }

    public function test_plan_feature_flags(): void
    {
        $freePlan = $this->makePlan([
            'allow_image_upload' => false,
            'allow_export_word' => false,
            'allow_all_providers' => false,
        ]);

        $proPlan = $this->makePlan([
            'allow_image_upload' => true,
            'allow_export_word' => true,
            'allow_all_providers' => true,
        ]);

        $freeUser = $this->makeUser(['subscription_plan_id' => $freePlan->id]);
        $proUser = $this->makeUser(['subscription_plan_id' => $proPlan->id]);

        $this->assertFalse($freeUser->canUseImageUpload());
        $this->assertFalse($freeUser->canExportWord());
        $this->assertFalse($freeUser->canUseAllProviders());

        $this->assertTrue($proUser->canUseImageUpload());
        $this->assertTrue($proUser->canExportWord());
        $this->assertTrue($proUser->canUseAllProviders());
    }
}
