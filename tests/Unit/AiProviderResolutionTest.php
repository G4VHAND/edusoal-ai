<?php

namespace Tests\Unit;

use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guru: provider 100% ditentukan admin sekolah, tidak bisa dipilih sendiri
 * apapun yang dikirim dari form. Individual: boleh pilih sendiri HANYA
 * kalau plan-nya allow_all_providers.
 */
class AiProviderResolutionTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(bool $allowAllProviders): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Test Plan',
            'slug' => 'test-'.uniqid(),
            'price_monthly' => 0,
            'price_yearly' => 0,
            'max_teachers' => 5,
            'quota_per_month' => 10,
            'max_questions_per_generate' => 20,
            'allow_image_upload' => false,
            'allow_export_word' => true,
            'allow_export_pdf' => true,
            'allow_all_providers' => $allowAllProviders,
            'is_active' => true,
        ]);
    }

    private function makeSchool(?string $aiProvider = null): School
    {
        return School::create([
            'name' => 'Sekolah Test '.uniqid(),
            'email' => uniqid().'@sekolah.test',
            'level' => 'smp',
            'is_active' => true,
            'ai_provider' => $aiProvider,
        ]);
    }

    private function makeTeacher(School $school): User
    {
        return User::factory()->create([
            'role' => 'teacher',
            'school_id' => $school->id,
            'email_verified_at' => now(),
        ]);
    }

    private function makeIndividual(?SubscriptionPlan $plan = null): User
    {
        return User::factory()->create([
            'role' => 'individual',
            'school_id' => null,
            'subscription_plan_id' => $plan?->id,
            'email_verified_at' => now(),
        ]);
    }

    // ── Guru — provider ditentukan sekolah, form diabaikan ─────────────────

    public function test_teacher_always_uses_school_configured_provider(): void
    {
        $school = $this->makeSchool('groq');
        $teacher = $this->makeTeacher($school);

        // Walaupun guru "minta" gemini dari form, tetap dipaksa pakai
        // pengaturan sekolah (groq).
        $this->assertEquals('groq', $teacher->resolveAiProvider('gemini'));
    }

    public function test_teacher_falls_back_to_system_default_when_school_has_not_configured_provider(): void
    {
        $school = $this->makeSchool(null);
        $teacher = $this->makeTeacher($school);

        $this->assertEquals(config('ai.default'), $teacher->resolveAiProvider());
    }

    public function test_teacher_cannot_override_school_provider_even_without_request_value(): void
    {
        $school = $this->makeSchool('groq');
        $teacher = $this->makeTeacher($school);

        $this->assertEquals('groq', $teacher->resolveAiProvider(null));
    }

    // ── Individual — boleh pilih sendiri kalau plan mengizinkan ─────────────

    public function test_individual_with_allow_all_providers_can_choose_provider(): void
    {
        $plan = $this->makePlan(allowAllProviders: true);
        $individual = $this->makeIndividual($plan);

        $this->assertEquals('groq', $individual->resolveAiProvider('groq'));
    }

    public function test_individual_without_allow_all_providers_is_forced_to_system_default(): void
    {
        $plan = $this->makePlan(allowAllProviders: false);
        $individual = $this->makeIndividual($plan);

        // Minta 'groq', tapi plan tidak mengizinkan — dipaksa default.
        $this->assertEquals(config('ai.default'), $individual->resolveAiProvider('groq'));
    }

    public function test_individual_with_no_plan_at_all_is_forced_to_system_default(): void
    {
        $individual = $this->makeIndividual(null);

        $this->assertEquals(config('ai.default'), $individual->resolveAiProvider('groq'));
    }

    public function test_individual_with_allow_all_providers_but_invalid_requested_value_falls_back_to_default(): void
    {
        $plan = $this->makePlan(allowAllProviders: true);
        $individual = $this->makeIndividual($plan);

        $this->assertEquals(config('ai.default'), $individual->resolveAiProvider('provider-tidak-dikenal'));
    }
}
