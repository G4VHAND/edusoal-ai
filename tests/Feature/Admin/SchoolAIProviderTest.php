<?php

namespace Tests\Feature\Admin;

use App\Models\QuestionSet;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SchoolAIProviderTest extends TestCase
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
            'allow_all_providers' => false,
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

    private function makeActiveSubscription(School $school, SubscriptionPlan $plan): SchoolSubscription
    {
        return SchoolSubscription::create([
            'school_id' => $school->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount_paid' => $plan->price_monthly,
            'quota_used' => 0,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'quota_reset_at' => now()->addMonth(),
        ]);
    }

    private function makeSchoolAdmin(School $school): User
    {
        return User::factory()->create([
            'role' => 'school_admin',
            'school_id' => $school->id,
            'email_verified_at' => now(),
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

    // ── Halaman pengaturan provider — hanya school_admin ────────────────────

    public function test_school_admin_can_view_and_update_ai_provider_settings(): void
    {
        $school = $this->makeSchool();
        $admin = $this->makeSchoolAdmin($school);

        $this->actingAs($admin)->get('/admin/ai-provider')->assertOk();

        $response = $this->actingAs($admin)->post('/admin/ai-provider', [
            'ai_provider' => 'groq',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('groq', $school->fresh()->ai_provider);
    }

    public function test_teacher_cannot_access_ai_provider_settings_page(): void
    {
        $school = $this->makeSchool();
        $teacher = $this->makeTeacher($school);

        $this->actingAs($teacher)->get('/admin/ai-provider')->assertForbidden();
    }

    public function test_ai_provider_settings_rejects_unsupported_provider(): void
    {
        $school = $this->makeSchool();
        $admin = $this->makeSchoolAdmin($school);

        $response = $this->actingAs($admin)->post('/admin/ai-provider', [
            'ai_provider' => 'chatgpt-tidak-didukung',
        ]);

        $response->assertSessionHasErrors('ai_provider');
        $this->assertNull($school->fresh()->ai_provider);
    }

    // ── End-to-end: guru tidak bisa override provider sekolah lewat form ───

    public function test_teacher_generate_soal_uses_school_provider_even_if_form_is_tampered(): void
    {
        Queue::fake();

        $plan = $this->makePlan();
        $school = $this->makeSchool(['ai_provider' => 'groq']);
        $this->makeActiveSubscription($school, $plan);
        $teacher = $this->makeTeacher($school);

        // Guru mencoba (atau form-nya dimanipulasi) mengirim 'gemini',
        // padahal sekolah sudah diset ke 'groq' oleh admin sekolah.
        $response = $this->actingAs($teacher)->post('/generate-soal', [
            'title' => 'Tes Provider',
            'subject' => 'Matematika',
            'grade' => 'Kelas 9 SMP',
            'topic' => 'Aljabar',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'curriculum' => 'merdeka',
            'assessment_type' => 'reguler',
            'total_questions' => 5,
            'ai_provider' => 'gemini',
        ]);

        $response->assertSessionHasNoErrors();

        $questionSet = QuestionSet::where('user_id', $teacher->id)->firstOrFail();

        // Harus tetap 'groq' (pengaturan sekolah), BUKAN 'gemini' yang
        // dikirim dari form.
        $this->assertEquals('groq', $questionSet->ai_provider);
    }
}
