<?php

namespace Tests\Feature\DocumentTemplate;

use App\Models\DocumentTemplate;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTemplateIndexTest extends TestCase
{
    use RefreshDatabase;

    private function makeSchool(array $attrs = []): School
    {
        return School::create(array_merge([
            'name' => 'SMPN 1 Contoh',
            'email' => fake()->unique()->safeEmail(),
            'level' => 'smp',
            'is_active' => true,
        ], $attrs));
    }

    private function makeTeacher(School $school, array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'role' => 'teacher',
            'school_id' => $school->id,
            'quota_used_this_month' => 0,
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ], $attrs));
    }

    private function makeSchoolAdmin(School $school, array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'role' => 'school_admin',
            'school_id' => $school->id,
            'quota_used_this_month' => 0,
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ], $attrs));
    }

    private function makeIndividual(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'role' => 'individual',
            'school_id' => null,
            'quota_used_this_month' => 0,
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ], $attrs));
    }

    private function makeSchoolTemplate(School $school, array $attrs = []): DocumentTemplate
    {
        return DocumentTemplate::create(array_merge([
            'school_id' => $school->id,
            'user_id' => null,
            'name' => 'Format UTS Sekolah',
            'file_path' => 'document-templates/fake-school-template.docx',
            'original_filename' => 'format-uts.docx',
            'type' => 'guru',
            'is_default' => true,
        ], $attrs));
    }

    // ── Guru: TIDAK punya akses sama sekali ke fitur template ──────────────
    // (Masukan pembimbing: guru tidak perlu tahu soal template, export
    // mereka otomatis pakai template default sekolah di belakang layar.)

    public function test_teacher_cannot_access_templates_index(): void
    {
        $school = $this->makeSchool();
        $teacher = $this->makeTeacher($school);
        $this->makeSchoolTemplate($school);

        $response = $this->actingAs($teacher)->get('/templates');

        $response->assertForbidden();
    }

    public function test_teacher_cannot_access_templates_create_page(): void
    {
        $school = $this->makeSchool();
        $teacher = $this->makeTeacher($school);

        $response = $this->actingAs($teacher)->get('/templates/create');

        $response->assertForbidden();
    }

    public function test_teacher_cannot_upload_template(): void
    {
        $school = $this->makeSchool();
        $teacher = $this->makeTeacher($school);

        $response = $this->actingAs($teacher)->post('/templates', [
            'name' => 'Coba Upload',
            'type' => 'guru',
        ]);

        $response->assertForbidden();
    }

    public function test_teacher_cannot_delete_school_template(): void
    {
        $school = $this->makeSchool();
        $teacher = $this->makeTeacher($school);
        $template = $this->makeSchoolTemplate($school);

        $response = $this->actingAs($teacher)->delete("/templates/{$template->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('document_templates', ['id' => $template->id]);
    }

    public function test_teacher_cannot_set_school_template_as_default(): void
    {
        $school = $this->makeSchool();
        $teacher = $this->makeTeacher($school);
        $template = $this->makeSchoolTemplate($school, ['is_default' => false]);

        $response = $this->actingAs($teacher)->patch("/templates/{$template->id}/set-default");

        $response->assertForbidden();
    }

    public function test_teacher_sidebar_does_not_show_template_menu(): void
    {
        $school = $this->makeSchool();
        $teacher = $this->makeTeacher($school);

        $response = $this->actingAs($teacher)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('Template Dokumen');
    }

    // ── Individual: tetap bisa kelola template personalnya sendiri ─────────

    public function test_individual_can_access_templates_index(): void
    {
        $individual = $this->makeIndividual();

        $response = $this->actingAs($individual)->get('/templates');

        $response->assertOk();
        $response->assertSee('Belum ada template diupload. Export soal akan menggunakan format standar.');
    }

    // ── School Admin: tetap bisa kelola template sekolahnya seperti biasa ──

    public function test_school_admin_index_shows_school_templates(): void
    {
        $school = $this->makeSchool();
        $admin = $this->makeSchoolAdmin($school);
        $template = $this->makeSchoolTemplate($school);

        $response = $this->actingAs($admin)->get('/templates');

        $response->assertOk();
        $response->assertSee($template->name);
    }

    public function test_school_admin_cannot_see_other_schools_template(): void
    {
        $schoolA = $this->makeSchool(['name' => 'SMPN 1 Contoh A', 'email' => 'a@sekolah.test']);
        $schoolB = $this->makeSchool(['name' => 'SMPN 2 Contoh B', 'email' => 'b@sekolah.test']);
        $adminA = $this->makeSchoolAdmin($schoolA);
        $templateB = $this->makeSchoolTemplate($schoolB, ['name' => 'Template Sekolah B']);

        $response = $this->actingAs($adminA)->get('/templates');

        $response->assertOk();
        $response->assertDontSee('Template Sekolah B');
    }
}
