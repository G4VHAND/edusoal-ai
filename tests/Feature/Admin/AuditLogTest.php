<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuperAdmin(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'super_admin',
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ]);
    }

    private function makeTeacher(?int $schoolId = null): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'teacher',
            'school_id' => $schoolId,
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

    // ── Access Control ────────────────────────────────────────────────────────

    public function test_super_admin_can_access_audit_log_page(): void
    {
        $admin = $this->makeSuperAdmin();

        $response = $this->actingAs($admin)->get('/admin/audit-log');

        $response->assertOk();
        $response->assertSee('Audit Log');
    }

    public function test_school_admin_can_access_audit_log_page(): void
    {
        $school = $this->makeSchool();
        $admin = $this->makeSchoolAdmin($school);

        $response = $this->actingAs($admin)->get('/admin/audit-log');

        $response->assertOk();
        $response->assertSee('Audit Log');
    }

    public function test_teacher_cannot_access_audit_log_page(): void
    {
        $teacher = $this->makeTeacher();

        $response = $this->actingAs($teacher)->get('/admin/audit-log');

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_audit_log_page(): void
    {
        $response = $this->get('/admin/audit-log');

        $response->assertRedirect('/login');
    }

    // ── Scoping ────────────────────────────────────────────────────────────────

    public function test_school_admin_only_sees_logs_from_own_school(): void
    {
        $ownSchool = $this->makeSchool(['name' => 'Sekolah Milik Sendiri']);
        $otherSchool = $this->makeSchool(['name' => 'Sekolah Lain']);

        $ownTeacher = $this->makeTeacher($ownSchool->id);
        $otherTeacher = $this->makeTeacher($otherSchool->id);

        AuditLog::create([
            'user_id' => $ownTeacher->id,
            'school_id' => $ownSchool->id,
            'module' => 'Bank Soal',
            'event' => 'create',
            'description' => 'Membuat bank soal milik sekolah sendiri',
        ]);

        AuditLog::create([
            'user_id' => $otherTeacher->id,
            'school_id' => $otherSchool->id,
            'module' => 'Bank Soal',
            'event' => 'create',
            'description' => 'Membuat bank soal milik sekolah lain',
        ]);

        $admin = $this->makeSchoolAdmin($ownSchool);
        $response = $this->actingAs($admin)->get('/admin/audit-log');

        $response->assertOk();
        $response->assertSee('Membuat bank soal milik sekolah sendiri');
        $response->assertDontSee('Membuat bank soal milik sekolah lain');
    }

    public function test_school_admin_cannot_bypass_scope_via_school_id_query_param(): void
    {
        $ownSchool = $this->makeSchool(['name' => 'Sekolah Milik Sendiri']);
        $otherSchool = $this->makeSchool(['name' => 'Sekolah Lain']);
        $otherTeacher = $this->makeTeacher($otherSchool->id);

        AuditLog::create([
            'user_id' => $otherTeacher->id,
            'school_id' => $otherSchool->id,
            'module' => 'Bank Soal',
            'event' => 'create',
            'description' => 'Aktivitas rahasia sekolah lain',
        ]);

        $admin = $this->makeSchoolAdmin($ownSchool);

        // Coba manipulasi query string ?school_id=<sekolah lain> — harus
        // tetap di-scope ke sekolah sendiri, karena filter school_id di
        // controller hanya dihormati untuk super_admin.
        $response = $this->actingAs($admin)
            ->get('/admin/audit-log?school_id='.$otherSchool->id);

        $response->assertOk();
        $response->assertDontSee('Aktivitas rahasia sekolah lain');
    }

    public function test_super_admin_can_view_logs_from_all_schools(): void
    {
        $schoolA = $this->makeSchool(['name' => 'Sekolah A']);
        $schoolB = $this->makeSchool(['name' => 'Sekolah B']);

        AuditLog::create([
            'user_id' => $this->makeTeacher($schoolA->id)->id,
            'school_id' => $schoolA->id,
            'module' => 'Bank Soal',
            'event' => 'create',
            'description' => 'Aktivitas dari sekolah A',
        ]);

        AuditLog::create([
            'user_id' => $this->makeTeacher($schoolB->id)->id,
            'school_id' => $schoolB->id,
            'module' => 'Bank Soal',
            'event' => 'create',
            'description' => 'Aktivitas dari sekolah B',
        ]);

        $admin = $this->makeSuperAdmin();
        $response = $this->actingAs($admin)->get('/admin/audit-log');

        $response->assertOk();
        $response->assertSee('Aktivitas dari sekolah A');
        $response->assertSee('Aktivitas dari sekolah B');
    }

    public function test_audit_log_can_be_filtered_by_module(): void
    {
        $school = $this->makeSchool();
        $teacher = $this->makeTeacher($school->id);

        AuditLog::create([
            'user_id' => $teacher->id,
            'school_id' => $school->id,
            'module' => 'Bank Soal',
            'event' => 'create',
            'description' => 'Aktivitas modul bank soal',
        ]);

        AuditLog::create([
            'user_id' => $teacher->id,
            'school_id' => $school->id,
            'module' => 'Soal',
            'event' => 'delete',
            'description' => 'Aktivitas modul soal',
        ]);

        $admin = $this->makeSchoolAdmin($school);
        $response = $this->actingAs($admin)->get('/admin/audit-log?module=Bank+Soal');

        $response->assertOk();
        $response->assertSee('Aktivitas modul bank soal');
        $response->assertDontSee('Aktivitas modul soal');
    }
}
