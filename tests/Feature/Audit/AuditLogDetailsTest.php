<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuditLogDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function makeVerifiedUser(array $attrs = []): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'individual',
            'quota_used_this_month' => 0,
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
            ...$attrs,
        ]);
    }

    private function makeQuestionSet(User $user, array $attrs = []): QuestionSet
    {
        return QuestionSet::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Bank Soal Test',
            'subject' => 'Matematika',
            'grade' => 'Kelas 9 SMP',
            'topic' => 'Aljabar',
            'question_type' => 'multiple_choice',
            'difficulty' => 'mudah',
            'curriculum' => 'merdeka',
            'assessment_type' => 'reguler',
            'total_questions' => 3,
            'ai_provider' => 'gemini',
            'status' => 'completed',
            'is_ai_generated' => true,
        ], $attrs));
    }

    private function makeQuestion(QuestionSet $set, array $attrs = []): Question
    {
        return Question::create(array_merge([
            'question_set_id' => $set->id,
            'question_text' => 'Soal contoh?',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'A',
        ], $attrs));
    }

    // ── AuditLogService::diff() ─────────────────────────────────────────────

    public function test_diff_only_returns_changed_fields(): void
    {
        $before = ['curriculum' => 'merdeka', 'total_questions' => 20, 'title' => 'Sama'];
        $after = ['curriculum' => 'k13', 'total_questions' => 50, 'title' => 'Sama'];

        $changes = AuditLogService::diff($before, $after);

        $this->assertArrayHasKey('curriculum', $changes);
        $this->assertArrayHasKey('total_questions', $changes);
        $this->assertArrayNotHasKey('title', $changes);
        $this->assertSame('merdeka', $changes['curriculum']['before']);
        $this->assertSame('k13', $changes['curriculum']['after']);
        $this->assertSame(20, $changes['total_questions']['before']);
        $this->assertSame(50, $changes['total_questions']['after']);
    }

    // ── Update: before/after diff ───────────────────────────────────────────

    public function test_updating_question_set_logs_field_level_changes(): void
    {
        $user = $this->makeVerifiedUser();
        $set = $this->makeQuestionSet($user);
        $this->makeQuestion($set);
        $this->makeQuestion($set);
        $this->makeQuestion($set);

        $response = $this->actingAs($user)->put("/bank-soal/{$set->id}", [
            'title' => $set->title,
            'subject' => $set->subject,
            'grade' => $set->grade,
            'topic' => $set->topic,
            'question_type' => $set->question_type,
            'difficulty' => $set->difficulty,
            'curriculum' => 'k13', // diubah dari 'merdeka'
            'assessment_type' => $set->assessment_type,
            'total_questions' => 3, // tidak berubah
        ]);

        $response->assertRedirect();

        $log = AuditLog::where('module', 'Bank Soal')->where('event', 'update')->latest()->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('curriculum', $log->properties['changes']);
        $this->assertSame('merdeka', $log->properties['changes']['curriculum']['before']);
        $this->assertSame('k13', $log->properties['changes']['curriculum']['after']);
        $this->assertArrayNotHasKey('title', $log->properties['changes']);
        $this->assertStringContainsString('curriculum', $log->description);
    }

    // ── Delete: snapshot survives the actual row being gone ────────────────

    public function test_deleting_question_set_keeps_snapshot_in_properties(): void
    {
        $user = $this->makeVerifiedUser();
        $set = $this->makeQuestionSet($user, ['title' => 'UTS IPA', 'total_questions' => 3]);
        $this->makeQuestion($set);

        $response = $this->actingAs($user)->delete("/bank-soal/{$set->id}");
        $response->assertRedirect();

        $log = AuditLog::where('module', 'Bank Soal')->where('event', 'delete')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame('UTS IPA', $log->properties['title']);
        $this->assertSame(3, $log->properties['total_questions']);
    }

    // ── Question delete: readable, numbered description ─────────────────────

    public function test_deleting_single_question_logs_its_number(): void
    {
        $user = $this->makeVerifiedUser();
        $set = $this->makeQuestionSet($user);
        $q1 = $this->makeQuestion($set);
        $q2 = $this->makeQuestion($set);

        $response = $this->actingAs($user)->delete("/bank-soal/{$set->id}/questions/{$q2->id}");
        $response->assertRedirect();

        $log = AuditLog::where('module', 'Soal')->where('event', 'delete')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame(2, $log->properties['question_number']);
        $this->assertStringContainsString('soal nomor 2', $log->description);
    }

    // ── Store: logs both the "create" and the "AI generate started" events ──

    public function test_store_logs_create_and_ai_generate_started(): void
    {
        Queue::fake();

        $user = $this->makeVerifiedUser();

        $response = $this->actingAs($user)->post('/generate-soal', [
            'title' => 'UTS Matematika',
            'subject' => 'Matematika',
            'grade' => 'Kelas 9 SMP',
            'topic' => 'Persamaan Linear',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'curriculum' => 'merdeka',
            'assessment_type' => 'reguler',
            'total_questions' => 5,
            'ai_provider' => 'gemini',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['module' => 'Bank Soal', 'event' => 'create']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'AI', 'event' => 'generate']);
    }

    // ── Authentication ───────────────────────────────────────────────────────

    public function test_login_is_logged(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Authentication',
            'event' => 'login',
            'user_id' => $user->id,
        ]);
    }

    public function test_logout_is_logged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Authentication',
            'event' => 'logout',
            'user_id' => $user->id,
        ]);
    }
}
