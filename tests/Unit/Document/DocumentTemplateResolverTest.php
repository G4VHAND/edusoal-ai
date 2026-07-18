<?php

namespace Tests\Unit\Document;

use App\Models\DocumentTemplate;
use App\Models\QuestionSet;
use App\Models\School;
use App\Models\User;
use App\Services\Document\DocumentTemplateResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * DocumentTemplateResolver menentukan template Word mana yang dipakai saat
 * export — logikanya bercabang (prioritas sekolah pembuat soal > sekolah
 * user login > personal) dan ada pengecekan otorisasi manual untuk
 * template_id eksplisit. Test ini membuktikan tiap cabang & celah
 * keamanannya benar, karena kalau ini salah, guru bisa export pakai
 * template sekolah lain atau sebaliknya tidak dapat template yang
 * seharusnya dia berhak pakai.
 */
class DocumentTemplateResolverTest extends TestCase
{
    use RefreshDatabase;

    private DocumentTemplateResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new DocumentTemplateResolver;
    }

    private function makeSchool(array $attrs = []): School
    {
        return School::create(array_merge([
            'name' => 'Sekolah '.uniqid(),
            'email' => uniqid().'@sekolah.test',
            'level' => 'smp',
            'is_active' => true,
        ], $attrs));
    }

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'is_active' => true,
        ], $attrs));
    }

    private function makeQuestionSet(User $owner): QuestionSet
    {
        return QuestionSet::create([
            'user_id' => $owner->id,
            'title' => 'Test',
            'subject' => 'Matematika',
            'grade' => 'Kelas 9 SMP',
            'topic' => 'Aljabar',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'total_questions' => 1,
            'status' => 'completed',
        ]);
    }

    private function makeTemplate(array $attrs = []): DocumentTemplate
    {
        return DocumentTemplate::create(array_merge([
            'name' => 'Template Test',
            'file_path' => 'document-templates/fake.docx',
            'original_filename' => 'fake.docx',
            'type' => 'guru',
            'is_default' => true,
        ], $attrs));
    }

    // ── resolveDefault: prioritas sekolah PEMBUAT SOAL ──────────────────────

    public function test_uses_default_template_from_question_set_owners_school(): void
    {
        $school = $this->makeSchool();
        $teacher = $this->makeUser(['role' => 'teacher', 'school_id' => $school->id]);
        $questionSet = $this->makeQuestionSet($teacher);
        $template = $this->makeTemplate(['school_id' => $school->id, 'type' => 'guru']);

        $result = $this->resolver->resolve($questionSet, $teacher, 'guru', null);

        $this->assertTrue($result->is($template));
    }

    public function test_ignores_non_default_template_even_if_school_matches(): void
    {
        $school = $this->makeSchool();
        $teacher = $this->makeUser(['role' => 'teacher', 'school_id' => $school->id]);
        $questionSet = $this->makeQuestionSet($teacher);
        $this->makeTemplate(['school_id' => $school->id, 'type' => 'guru', 'is_default' => false]);

        $result = $this->resolver->resolve($questionSet, $teacher, 'guru', null);

        $this->assertNull($result);
    }

    public function test_ignores_template_of_wrong_type(): void
    {
        $school = $this->makeSchool();
        $teacher = $this->makeUser(['role' => 'teacher', 'school_id' => $school->id]);
        $questionSet = $this->makeQuestionSet($teacher);
        $this->makeTemplate(['school_id' => $school->id, 'type' => 'siswa', 'is_default' => true]);

        // Minta type 'guru', yang ada di DB cuma type 'siswa' — tidak boleh ketuker.
        $result = $this->resolver->resolve($questionSet, $teacher, 'guru', null);

        $this->assertNull($result);
    }

    // ── resolveDefault: super_admin export soal guru lain ───────────────────

    public function test_super_admin_exporting_other_teachers_soal_gets_that_teachers_school_template(): void
    {
        $teacherSchool = $this->makeSchool(['name' => 'Sekolah Guru']);
        $teacher = $this->makeUser(['role' => 'teacher', 'school_id' => $teacherSchool->id]);
        $questionSet = $this->makeQuestionSet($teacher);
        $teacherTemplate = $this->makeTemplate(['school_id' => $teacherSchool->id, 'type' => 'guru']);

        $superAdmin = $this->makeUser(['role' => 'super_admin', 'school_id' => null]);

        // super_admin tidak terikat sekolah manapun, tapi soal yang di-export
        // milik guru dari "Sekolah Guru" — harus dapat template sekolah ITU,
        // bukan gagal cuma karena super_admin sendiri tidak punya sekolah.
        $result = $this->resolver->resolve($questionSet, $superAdmin, 'guru', null);

        $this->assertTrue($result->is($teacherTemplate));
    }

    // ── resolveDefault: fallback ke sekolah user yang login ─────────────────

    public function test_falls_back_to_current_users_school_when_question_set_owner_has_no_template(): void
    {
        $ownerSchool = $this->makeSchool(['name' => 'Sekolah Pemilik Soal']);
        $owner = $this->makeUser(['role' => 'teacher', 'school_id' => $ownerSchool->id]);
        $questionSet = $this->makeQuestionSet($owner);
        // Sekolah pemilik soal TIDAK punya template default.

        $loginUserSchool = $this->makeSchool(['name' => 'Sekolah User Login']);
        $loginUser = $this->makeUser(['role' => 'school_admin', 'school_id' => $loginUserSchool->id]);
        $fallbackTemplate = $this->makeTemplate(['school_id' => $loginUserSchool->id, 'type' => 'guru']);

        $result = $this->resolver->resolve($questionSet, $loginUser, 'guru', null);

        $this->assertTrue($result->is($fallbackTemplate));
    }

    // ── resolveDefault: fallback ke template personal ───────────────────────

    public function test_falls_back_to_personal_template_when_no_school_template_exists(): void
    {
        $individual = $this->makeUser(['role' => 'individual', 'school_id' => null]);
        $questionSet = $this->makeQuestionSet($individual);
        $personalTemplate = $this->makeTemplate(['user_id' => $individual->id, 'type' => 'guru']);

        $result = $this->resolver->resolve($questionSet, $individual, 'guru', null);

        $this->assertTrue($result->is($personalTemplate));
    }

    public function test_returns_null_when_nothing_found_anywhere(): void
    {
        $individual = $this->makeUser(['role' => 'individual', 'school_id' => null]);
        $questionSet = $this->makeQuestionSet($individual);

        $result = $this->resolver->resolve($questionSet, $individual, 'guru', null);

        $this->assertNull($result);
    }

    // ── resolveExplicit: template_id dikirim manual ──────────────────────────

    public function test_explicit_template_id_returns_template_when_owned_by_school_admin(): void
    {
        $school = $this->makeSchool();
        $admin = $this->makeUser(['role' => 'school_admin', 'school_id' => $school->id]);
        $questionSet = $this->makeQuestionSet($admin);
        $template = $this->makeTemplate(['school_id' => $school->id]);

        $result = $this->resolver->resolve($questionSet, $admin, 'guru', $template->id);

        $this->assertTrue($result->is($template));
    }

    public function test_explicit_template_id_returns_null_when_template_does_not_exist(): void
    {
        $individual = $this->makeUser(['role' => 'individual', 'school_id' => null]);
        $questionSet = $this->makeQuestionSet($individual);

        $result = $this->resolver->resolve($questionSet, $individual, 'guru', 999999);

        $this->assertNull($result);
    }

    public function test_explicit_template_id_forbidden_when_school_admin_uses_other_schools_template(): void
    {
        $schoolA = $this->makeSchool(['name' => 'Sekolah A']);
        $schoolB = $this->makeSchool(['name' => 'Sekolah B']);
        $adminA = $this->makeUser(['role' => 'school_admin', 'school_id' => $schoolA->id]);
        $questionSet = $this->makeQuestionSet($adminA);
        $templateB = $this->makeTemplate(['school_id' => $schoolB->id]);

        $this->expectException(HttpException::class);

        $this->resolver->resolve($questionSet, $adminA, 'guru', $templateB->id);
    }

    public function test_explicit_template_id_allows_teacher_to_use_own_schools_default_template(): void
    {
        // Guru bukan "pemilik" template dalam arti isOwnedBy() (yang upload
        // biasanya school_admin), tapi guru tetap boleh pakai template
        // default sekolahnya sendiri secara eksplisit saat export.
        $school = $this->makeSchool();
        $teacher = $this->makeUser(['role' => 'teacher', 'school_id' => $school->id]);
        $questionSet = $this->makeQuestionSet($teacher);
        $template = $this->makeTemplate(['school_id' => $school->id]); // diupload admin, user_id null

        $result = $this->resolver->resolve($questionSet, $teacher, 'guru', $template->id);

        $this->assertTrue($result->is($template));
    }

    public function test_explicit_template_id_forbidden_when_teacher_uses_different_schools_template(): void
    {
        $schoolA = $this->makeSchool(['name' => 'Sekolah A']);
        $schoolB = $this->makeSchool(['name' => 'Sekolah B']);
        $teacherA = $this->makeUser(['role' => 'teacher', 'school_id' => $schoolA->id]);
        $questionSet = $this->makeQuestionSet($teacherA);
        $templateB = $this->makeTemplate(['school_id' => $schoolB->id]);

        $this->expectException(HttpException::class);

        $this->resolver->resolve($questionSet, $teacherA, 'guru', $templateB->id);
    }

    public function test_explicit_template_id_allows_individual_to_use_own_personal_template(): void
    {
        $individual = $this->makeUser(['role' => 'individual', 'school_id' => null]);
        $questionSet = $this->makeQuestionSet($individual);
        $template = $this->makeTemplate(['user_id' => $individual->id]);

        $result = $this->resolver->resolve($questionSet, $individual, 'guru', $template->id);

        $this->assertTrue($result->is($template));
    }

    public function test_explicit_template_id_forbidden_when_individual_uses_other_users_template(): void
    {
        $individualA = $this->makeUser(['role' => 'individual', 'school_id' => null]);
        $individualB = $this->makeUser(['role' => 'individual', 'school_id' => null]);
        $questionSet = $this->makeQuestionSet($individualA);
        $templateB = $this->makeTemplate(['user_id' => $individualB->id]);

        $this->expectException(HttpException::class);

        $this->resolver->resolve($questionSet, $individualA, 'guru', $templateB->id);
    }
}
