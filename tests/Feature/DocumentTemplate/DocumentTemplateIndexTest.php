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
            'name'      => 'SMPN 1 Contoh',
            'email'     => fake()->unique()->safeEmail(),
            'level'     => 'smp',
            'is_active' => true,
        ], $attrs));
    }

    private function makeTeacher(School $school, array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at'     => now(),
            'role'                  => 'teacher',
            'school_id'             => $school->id,
            'quota_used_this_month' => 0,
            'quota_reset_at'        => now()->addMonth(),
            'is_active'             => true,
        ], $attrs));
    }

    private function makeSchoolAdmin(School $school, array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at'     => now(),
            'role'                  => 'school_admin',
            'school_id'             => $school->id,
            'quota_used_this_month' => 0,
            'quota_reset_at'        => now()->addMonth(),
            'is_active'             => true,
        ], $attrs));
    }

    private function makeSchoolTemplate(School $school, array $attrs = []): DocumentTemplate
    {
        return DocumentTemplate::create(array_merge([
            'school_id'          => $school->id,
            'user_id'            => null,
            'name'               => 'Format UTS Sekolah',
            'file_path'          => 'document-templates/fake-school-template.docx',
            'original_filename'  => 'format-uts.docx',
            'type'               => 'guru',
            'is_default'         => true,
        ], $attrs));
    }

    public function test_teacher_sees_school_template_on_index_page(): void
    {
        $school   = $this->makeSchool();
        $teacher  = $this->makeTeacher($school);
        $template = $this->makeSchoolTemplate($school);

        $response = $this->actingAs($teacher)->get('/templates');

        $response->assertOk();
        $response->assertSee('Template dari Sekolah');
        $response->assertSee($template->name);
        $response->assertSee('Dipakai otomatis');
    }

    public function test_teacher_without_personal_template_sees_fallback_message_not_standard_format_message(): void
    {
        $school  = $this->makeSchool();
        $teacher = $this->makeTeacher($school);
        $this->makeSchoolTemplate($school);

        $response = $this->actingAs($teacher)->get('/templates');

        $response->assertOk();
        $response->assertSee('otomatis memakai template sekolah');
        $response->assertDontSee('Export soal akan menggunakan format standar.');
    }

    public function test_teacher_without_school_template_sees_standard_format_message(): void
    {
        $school  = $this->makeSchool();
        $teacher = $this->makeTeacher($school);
        // Tidak ada template sekolah maupun personal.

        $response = $this->actingAs($teacher)->get('/templates');

        $response->assertOk();
        $response->assertSee('Export soal akan menggunakan format standar.');
    }

    public function test_teacher_cannot_see_other_schools_template(): void
    {
        $schoolA  = $this->makeSchool(['name' => 'SMPN 1 Contoh A', 'email' => 'a@sekolah.test']);
        $schoolB  = $this->makeSchool(['name' => 'SMPN 2 Contoh B', 'email' => 'b@sekolah.test']);
        $teacherA = $this->makeTeacher($schoolA);
        $templateB = $this->makeSchoolTemplate($schoolB, ['name' => 'Template Sekolah B']);

        $response = $this->actingAs($teacherA)->get('/templates');

        $response->assertOk();
        $response->assertDontSee('Template Sekolah B');
    }

    public function test_teacher_cannot_delete_school_template(): void
    {
        $school   = $this->makeSchool();
        $teacher  = $this->makeTeacher($school);
        $template = $this->makeSchoolTemplate($school);

        $response = $this->actingAs($teacher)->delete("/templates/{$template->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('document_templates', ['id' => $template->id]);
    }

    public function test_teacher_cannot_set_school_template_as_default(): void
    {
        $school   = $this->makeSchool();
        $teacher  = $this->makeTeacher($school);
        $template = $this->makeSchoolTemplate($school, ['is_default' => false]);

        $response = $this->actingAs($teacher)->patch("/templates/{$template->id}/set-default");

        $response->assertForbidden();
    }

    public function test_school_admin_index_unaffected_by_school_templates_section(): void
    {
        // School admin tetap melihat & bisa kelola template sekolahnya seperti biasa
        // (regresi check — perubahan index() untuk guru tidak boleh mengubah perilaku admin).
        $school = $this->makeSchool();
        $admin  = $this->makeSchoolAdmin($school);
        $template = $this->makeSchoolTemplate($school);

        $response = $this->actingAs($admin)->get('/templates');

        $response->assertOk();
        $response->assertSee($template->name);
        $response->assertDontSee('Template dari Sekolah'); // section khusus guru saja
    }
}
