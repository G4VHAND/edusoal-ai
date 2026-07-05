<?php

namespace Tests\Feature\Admin;

use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Free',
            'slug' => 'free',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'max_teachers' => 1,
            'quota_per_month' => 10,
            'max_questions_per_generate' => 5,
            'allow_image_upload' => false,
            'allow_export_word' => false,
            'allow_export_pdf' => true,
            'allow_all_providers' => false,
            'is_active' => true,
        ]);
    }

    private function makeSuperAdmin(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'super_admin',
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ]);
    }

    private function makeTeacher(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'teacher',
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ]);
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

    private function makeSchoolAdmin(School $school): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'school_admin',
            'school_id' => $school->id,
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ]);
    }

    private function makeSchoolSubscription(School $school, SubscriptionPlan $plan): void
    {
        SchoolSubscription::create([
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

    // ── Access Control ────────────────────────────────────────────────────────

    public function test_super_admin_can_access_admin_dashboard(): void
    {
        $admin = $this->makeSuperAdmin();
        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Admin Panel');
    }

    public function test_teacher_cannot_access_admin_panel(): void
    {
        $teacher = $this->makeTeacher();
        $response = $this->actingAs($teacher)->get('/admin');

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_admin(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_teacher_cannot_access_schools_management(): void
    {
        $teacher = $this->makeTeacher();
        $response = $this->actingAs($teacher)->get('/admin/schools');

        $response->assertForbidden();
    }

    // ── School Management ─────────────────────────────────────────────────────

    public function test_super_admin_can_view_schools_list(): void
    {
        $admin = $this->makeSuperAdmin();
        $response = $this->actingAs($admin)->get('/admin/schools');

        $response->assertOk();
        $response->assertSee('Manajemen Sekolah');
    }

    public function test_super_admin_can_create_school(): void
    {
        $admin = $this->makeSuperAdmin();
        $plan = $this->makePlan();

        $response = $this->actingAs($admin)->post('/admin/schools', [
            'name' => 'SMAN 1 Jakarta',
            'email' => 'sman1@test.com',
            'phone' => '021-1234567',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'level' => 'sma',
            'plan_slug' => 'free',
            'admin_name' => 'Admin Sekolah',
            'admin_email' => 'admin@sman1.com',
            'admin_password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.schools.index'));
        $this->assertDatabaseHas('schools', ['name' => 'SMAN 1 Jakarta']);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@sman1.com',
            'role' => 'school_admin',
        ]);
    }

    public function test_creating_school_also_creates_trial_subscription(): void
    {
        $admin = $this->makeSuperAdmin();
        $plan = $this->makePlan();

        $this->actingAs($admin)->post('/admin/schools', [
            'name' => 'SMPN 2 Bandung',
            'email' => 'smpn2@test.com',
            'level' => 'smp',
            'plan_slug' => 'free',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@smpn2.com',
            'admin_password' => 'password123',
        ]);

        $school = School::where('name', 'SMPN 2 Bandung')->first();
        $this->assertNotNull($school);
        $this->assertDatabaseHas('school_subscriptions', [
            'school_id' => $school->id,
            'status' => 'trial',
        ]);
    }

    public function test_super_admin_can_toggle_school_active_status(): void
    {
        $admin = $this->makeSuperAdmin();
        $school = School::create([
            'name' => 'Test School',
            'slug' => 'test-school',
            'email' => 'test@school.com',
            'level' => 'sma',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->patch("/admin/schools/{$school->id}/toggle-active");

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'is_active' => false,
        ]);
    }

    // ── Teacher Management ────────────────────────────────────────────────────

    public function test_super_admin_can_view_teachers_list(): void
    {
        $admin = $this->makeSuperAdmin();
        $response = $this->actingAs($admin)->get('/admin/teachers');

        $response->assertOk();
    }

    public function test_login_redirects_super_admin_to_admin_panel(): void
    {
        $admin = $this->makeSuperAdmin();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_login_redirects_teacher_to_dashboard(): void
    {
        $teacher = $this->makeTeacher();

        $response = $this->post('/login', [
            'email' => $teacher->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    // ── Dashboard Scoping (school admin harus HANYA lihat data sekolahnya) ──────

    public function test_school_admin_dashboard_only_shows_own_school_stats(): void
    {
        $plan = $this->makePlan();

        $schoolA = $this->makeSchool(['name' => 'Sekolah A']);
        $this->makeSchoolSubscription($schoolA, $plan);
        $adminA = $this->makeSchoolAdmin($schoolA);

        // 2 guru di sekolah A
        User::factory()->count(2)->create([
            'role' => 'teacher', 'school_id' => $schoolA->id,
            'email_verified_at' => now(), 'is_active' => true,
        ]);

        // Sekolah lain dengan lebih banyak guru — TIDAK boleh ikut kehitung.
        $schoolB = $this->makeSchool(['name' => 'Sekolah B Rahasia']);
        $this->makeSchoolSubscription($schoolB, $plan);
        User::factory()->count(10)->create([
            'role' => 'teacher', 'school_id' => $schoolB->id,
            'email_verified_at' => now(), 'is_active' => true,
        ]);

        $response = $this->actingAs($adminA)->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.school-dashboard');
        $response->assertViewHas('stats', fn ($stats) => $stats['total_teachers'] === 2);

        // Tidak boleh bocor nama/data sekolah lain.
        $response->assertDontSee('Sekolah B Rahasia');
        $response->assertDontSee('Distribusi Paket Langganan');
        $response->assertDontSee('Total Sekolah');
    }

    public function test_school_admin_sees_own_school_name_and_plan(): void
    {
        $plan = $this->makePlan();
        $school = $this->makeSchool(['name' => 'SMA Harapan Bangsa']);
        $this->makeSchoolSubscription($school, $plan);
        $admin = $this->makeSchoolAdmin($school);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('SMA Harapan Bangsa');
        $response->assertSee($plan->name);
    }

    public function test_super_admin_dashboard_still_shows_platform_wide_stats(): void
    {
        // Regresi — pastikan pemisahan controller tidak mengubah perilaku
        // lama untuk super admin.
        $plan = $this->makePlan();
        $school = $this->makeSchool();
        $this->makeSchoolSubscription($school, $plan);
        $admin = $this->makeSuperAdmin();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertSee('Total Sekolah');
        $response->assertSee('Distribusi Paket Langganan');
    }

    public function test_school_admin_without_school_gets_forbidden(): void
    {
        // Edge case: school_admin yang entah bagaimana tidak terhubung
        // ke sekolah manapun (school_id null) harus ditolak, bukan error.
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'school_admin',
            'school_id' => null,
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertForbidden();
    }

    // ── Dashboard biasa harus redirect admin, bukan jadi dead-end ───────────────

    public function test_super_admin_visiting_regular_dashboard_gets_redirected_to_admin(): void
    {
        $admin = $this->makeSuperAdmin();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_school_admin_visiting_regular_dashboard_gets_redirected_to_admin(): void
    {
        $plan = $this->makePlan();
        $school = $this->makeSchool();
        $this->makeSchoolSubscription($school, $plan);
        $admin = $this->makeSchoolAdmin($school);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_teacher_visiting_regular_dashboard_is_not_redirected(): void
    {
        // Regresi — pastikan fix ini tidak ikut mengganggu guru biasa.
        $teacher = $this->makeTeacher();

        $response = $this->actingAs($teacher)->get('/dashboard');

        $response->assertOk();
    }
}
