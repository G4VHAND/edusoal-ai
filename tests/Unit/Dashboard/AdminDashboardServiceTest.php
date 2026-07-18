<?php

namespace Tests\Unit\Dashboard;

use App\Models\QuestionSet;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Dashboard\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AdminDashboardService punya beberapa definisi bisnis yang sengaja tidak
 * intuitif (didokumentasikan di class itu sendiri) — test ini memastikan
 * definisi tsb diimplementasikan dengan benar, bukan cuma "kelihatannya
 * masuk akal".
 */
class AdminDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminDashboardService;
    }

    private function makeSchool(): School
    {
        return School::create([
            'name' => 'Sekolah '.uniqid(),
            'email' => uniqid().'@sekolah.test',
            'level' => 'smp',
            'is_active' => true,
        ]);
    }

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'is_active' => true,
        ], $attrs));
    }

    private function makeQuestionSet(User $user, array $attrs = []): QuestionSet
    {
        $qs = QuestionSet::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Test',
            'subject' => 'Matematika',
            'grade' => 'Kelas 9 SMP',
            'topic' => 'Aljabar',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'total_questions' => 1,
            'status' => 'completed',
        ], $attrs));

        if (isset($attrs['created_at']) || isset($attrs['updated_at'])) {
            if (isset($attrs['created_at'])) {
                $qs->created_at = $attrs['created_at'];
            }
            if (isset($attrs['updated_at'])) {
                $qs->updated_at = $attrs['updated_at'];
            }
            $qs->timestamps = false; // cegah save() menimpa updated_at otomatis
            $qs->save();
        }

        return $qs;
    }

    // ── Guru Aktif: berdasarkan aktivitas generate, BUKAN role sembarangan ──

    public function test_active_teachers_only_counts_teacher_role_within_30_days(): void
    {
        $teacher = $this->makeUser(['role' => 'teacher']);
        $individual = $this->makeUser(['role' => 'individual']);

        $this->makeQuestionSet($teacher, ['created_at' => now()->subDays(5)]);
        $this->makeQuestionSet($individual, ['created_at' => now()->subDays(5)]); // bukan guru

        $data = $this->service->superAdminOverview();

        $this->assertEquals(1, $data['widgets']['active_teachers']);
    }

    public function test_active_teachers_excludes_activity_older_than_30_days(): void
    {
        $teacher = $this->makeUser(['role' => 'teacher']);
        $this->makeQuestionSet($teacher, ['created_at' => now()->subDays(45)]);

        $data = $this->service->superAdminOverview();

        $this->assertEquals(0, $data['widgets']['active_teachers']);
    }

    public function test_active_teachers_counts_teacher_once_even_with_multiple_question_sets(): void
    {
        $teacher = $this->makeUser(['role' => 'teacher']);
        $this->makeQuestionSet($teacher, ['created_at' => now()]);
        $this->makeQuestionSet($teacher, ['created_at' => now()->subDays(1)]);
        $this->makeQuestionSet($teacher, ['created_at' => now()->subDays(2)]);

        $data = $this->service->superAdminOverview();

        $this->assertEquals(1, $data['widgets']['active_teachers']);
    }

    // ── Rata-rata waktu generate: format label ───────────────────────────────

    public function test_average_generate_label_shows_dash_when_no_completed_sets(): void
    {
        $data = $this->service->superAdminOverview();

        $this->assertEquals('—', $data['widgets']['avg_generate_label']);
    }

    public function test_average_generate_label_formats_under_a_minute_as_seconds_only(): void
    {
        $user = $this->makeUser();
        $created = now()->subSeconds(30);
        $this->makeQuestionSet($user, ['created_at' => $created, 'updated_at' => $created->copy()->addSeconds(30)]);

        $data = $this->service->superAdminOverview();

        $this->assertEquals('30s', $data['widgets']['avg_generate_label']);
    }

    public function test_average_generate_label_formats_over_a_minute_as_minutes_and_seconds(): void
    {
        $user = $this->makeUser();
        $created = now()->subSeconds(135); // 2m 15s
        $this->makeQuestionSet($user, ['created_at' => $created, 'updated_at' => $created->copy()->addSeconds(135)]);

        $data = $this->service->superAdminOverview();

        $this->assertEquals('2m 15s', $data['widgets']['avg_generate_label']);
    }

    // ── Kuota terpakai bulan ini: sekolah (aktif/trial) + individual ────────

    public function test_quota_used_sums_active_and_trial_school_subscriptions(): void
    {
        $school = $this->makeSchool();
        $plan = SubscriptionPlan::create([
            'name' => 'Basic', 'slug' => 'basic-'.uniqid(),
            'price_monthly' => 0, 'price_yearly' => 0,
            'max_teachers' => 5, 'quota_per_month' => 100,
            'max_questions_per_generate' => 20,
            'allow_image_upload' => false, 'allow_export_word' => true,
            'allow_export_pdf' => true, 'allow_all_providers' => false,
            'is_active' => true,
        ]);

        SchoolSubscription::create([
            'school_id' => $school->id, 'subscription_plan_id' => $plan->id,
            'status' => 'active', 'billing_cycle' => 'monthly',
            'amount_paid' => 0, 'quota_used' => 15,
            'starts_at' => now(), 'ends_at' => now()->addMonth(),
            'quota_reset_at' => now()->addMonth(),
        ]);

        SchoolSubscription::create([
            'school_id' => $this->makeSchool()->id, 'subscription_plan_id' => $plan->id,
            'status' => 'expired', 'billing_cycle' => 'monthly', // TIDAK boleh ikut dihitung
            'amount_paid' => 0, 'quota_used' => 999,
            'starts_at' => now()->subMonths(2), 'ends_at' => now()->subMonth(),
            'quota_reset_at' => now(),
        ]);

        $data = $this->service->superAdminOverview();

        $this->assertEquals(15, $data['widgets']['quota_used_this_month']);
    }

    public function test_quota_used_includes_individual_users_without_school(): void
    {
        $this->makeUser(['role' => 'individual', 'school_id' => null, 'quota_used_this_month' => 7]);
        // Guru dengan school_id TIDAK boleh ikut dihitung di sisi individual
        // (kuotanya sudah masuk lewat SchoolSubscription di atas).
        $school = $this->makeSchool();
        $this->makeUser(['role' => 'teacher', 'school_id' => $school->id, 'quota_used_this_month' => 999]);

        $data = $this->service->superAdminOverview();

        $this->assertEquals(7, $data['widgets']['quota_used_this_month']);
    }

    // ── Chart provider & jenis soal ──────────────────────────────────────────

    public function test_provider_chart_counts_per_provider(): void
    {
        $user = $this->makeUser();
        $this->makeQuestionSet($user, ['ai_provider' => 'gemini']);
        $this->makeQuestionSet($user, ['ai_provider' => 'gemini']);
        $this->makeQuestionSet($user, ['ai_provider' => 'groq']);

        $data = $this->service->superAdminOverview();

        $labels = $data['providerChart']['labels'];
        $totals = $data['providerChart']['totals'];
        $geminiIndex = array_search('Gemini', $labels);

        $this->assertNotFalse($geminiIndex);
        $this->assertEquals(2, $totals[$geminiIndex]);
    }

    public function test_question_type_chart_splits_multiple_choice_and_essay(): void
    {
        $user = $this->makeUser();
        $this->makeQuestionSet($user, ['question_type' => 'multiple_choice']);
        $this->makeQuestionSet($user, ['question_type' => 'multiple_choice']);
        $this->makeQuestionSet($user, ['question_type' => 'essay']);

        $data = $this->service->superAdminOverview();

        $this->assertEquals([2, 1], $data['questionTypeChart']['totals']);
    }

    // ── Widget hari ini / bulan ini ───────────────────────────────────────────

    public function test_generate_today_only_counts_todays_question_sets(): void
    {
        $user = $this->makeUser();
        $this->makeQuestionSet($user, ['created_at' => now()]);
        $this->makeQuestionSet($user, ['created_at' => now()->subDay()]);

        $data = $this->service->superAdminOverview();

        $this->assertEquals(1, $data['widgets']['generate_today']);
    }
}
