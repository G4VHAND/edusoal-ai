<?php

namespace Tests\Feature\QuestionSet;

use App\Jobs\AddQuestionsJob;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QuestionSetAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeVerifiedUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'role'              => 'individual',
            'quota_used_this_month' => 0,
            'quota_reset_at'    => now()->addMonth(),
            'is_active'         => true,
        ], $attrs));
    }

    private function makeQuestionSet(User $user, array $attrs = []): QuestionSet
    {
        return QuestionSet::create(array_merge([
            'user_id'         => $user->id,
            'title'           => 'Test Bank Soal',
            'subject'         => 'Matematika',
            'grade'           => 'Kelas 9 SMP',
            'topic'           => 'Aljabar',
            'question_type'   => 'multiple_choice',
            'difficulty'      => 'sedang',
            'total_questions' => 5,
            'ai_provider'     => 'gemini',
            'status'          => 'completed',
            'is_ai_generated' => true,
        ], $attrs));
    }

    private function makeQuestions(QuestionSet $questionSet, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Question::create([
                'question_set_id' => $questionSet->id,
                'question_text'   => "Soal ke-{$i}",
                'option_a'        => 'A', 'option_b' => 'B',
                'option_c'        => 'C', 'option_d' => 'D',
                'correct_answer'  => 'A',
            ]);
        }
    }

    // ── Unauthenticated ───────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_bank_soal(): void
    {
        $response = $this->get('/bank-soal');
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_user_cannot_access_generate_soal(): void
    {
        $response = $this->get('/generate-soal');
        $response->assertRedirect('/login');
    }

    // ── Authorization Policy ──────────────────────────────────────────────────

    public function test_user_can_view_own_question_set(): void
    {
        $user        = $this->makeVerifiedUser();
        $questionSet = $this->makeQuestionSet($user);

        $response = $this->actingAs($user)->get("/bank-soal/{$questionSet->id}");
        $response->assertOk();
    }

    public function test_user_cannot_view_other_users_question_set(): void
    {
        $owner       = $this->makeVerifiedUser();
        $other       = $this->makeVerifiedUser();
        $questionSet = $this->makeQuestionSet($owner);

        $response = $this->actingAs($other)->get("/bank-soal/{$questionSet->id}");
        $response->assertForbidden();
    }

    public function test_user_cannot_edit_other_users_question_set(): void
    {
        $owner       = $this->makeVerifiedUser();
        $other       = $this->makeVerifiedUser();
        $questionSet = $this->makeQuestionSet($owner);

        $response = $this->actingAs($other)->get("/bank-soal/{$questionSet->id}/edit");
        $response->assertForbidden();
    }

    public function test_user_cannot_delete_other_users_question_set(): void
    {
        $owner       = $this->makeVerifiedUser();
        $other       = $this->makeVerifiedUser();
        $questionSet = $this->makeQuestionSet($owner);

        $response = $this->actingAs($other)->delete("/bank-soal/{$questionSet->id}");
        $response->assertForbidden();
    }

    public function test_user_cannot_export_other_users_question_set(): void
    {
        $owner       = $this->makeVerifiedUser();
        $other       = $this->makeVerifiedUser();
        $questionSet = $this->makeQuestionSet($owner);

        $response = $this->actingAs($other)->get("/bank-soal/{$questionSet->id}/export-pdf");
        $response->assertForbidden();
    }

    // ── Soft Delete ───────────────────────────────────────────────────────────

    public function test_deleted_question_set_not_visible_in_bank_soal(): void
    {
        $user        = $this->makeVerifiedUser();
        $questionSet = $this->makeQuestionSet($user);
        $questionSet->delete();

        $response = $this->actingAs($user)->get('/bank-soal');
        $response->assertOk();
        $response->assertDontSee($questionSet->title);
    }

    public function test_soft_delete_does_not_permanently_remove_record(): void
    {
        $user        = $this->makeVerifiedUser();
        $questionSet = $this->makeQuestionSet($user);
        $id          = $questionSet->id;

        $this->actingAs($user)->delete("/bank-soal/{$id}");

        $this->assertSoftDeleted('question_sets', ['id' => $id]);
    }

    // ── Bank Soal Index ───────────────────────────────────────────────────────

    public function test_bank_soal_only_shows_own_question_sets(): void
    {
        $user1 = $this->makeVerifiedUser();
        $user2 = $this->makeVerifiedUser();

        $qs1 = $this->makeQuestionSet($user1, ['title' => 'Soal Milik User 1']);
        $qs2 = $this->makeQuestionSet($user2, ['title' => 'Soal Milik User 2']);

        $response = $this->actingAs($user1)->get('/bank-soal');
        $response->assertOk();
        $response->assertSee('Soal Milik User 1');
        $response->assertDontSee('Soal Milik User 2');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_user_can_update_own_question_set(): void
    {
        $user        = $this->makeVerifiedUser();
        $questionSet = $this->makeQuestionSet($user, ['total_questions' => 5]);
        $this->makeQuestions($questionSet, 5);

        // Jumlah soal TIDAK dinaikkan, jadi tidak memicu AddQuestionsJob.
        $response = $this->actingAs($user)->put("/bank-soal/{$questionSet->id}", [
            'title'           => 'Judul Baru',
            'subject'         => 'Matematika',
            'grade'           => 'Kelas 9 SMP',
            'topic'           => 'Aljabar',
            'question_type'   => 'multiple_choice',
            'difficulty'      => 'sulit',
            'curriculum'      => 'merdeka',
            'assessment_type' => 'reguler',
            'total_questions' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('question_sets', [
            'id'    => $questionSet->id,
            'title' => 'Judul Baru',
        ]);
    }

    public function test_increasing_total_questions_dispatches_add_questions_job(): void
    {
        Queue::fake();

        $user        = $this->makeVerifiedUser();
        $questionSet = $this->makeQuestionSet($user, ['total_questions' => 5]);
        $this->makeQuestions($questionSet, 5);

        $response = $this->actingAs($user)->put("/bank-soal/{$questionSet->id}", [
            'title'           => $questionSet->title,
            'subject'         => $questionSet->subject,
            'grade'           => $questionSet->grade,
            'topic'           => $questionSet->topic,
            'question_type'   => $questionSet->question_type,
            'difficulty'      => $questionSet->difficulty,
            'curriculum'      => 'merdeka',
            'assessment_type' => 'reguler',
            'total_questions' => 8,
        ]);

        $response->assertRedirect();
        Queue::assertPushed(AddQuestionsJob::class);

        $this->assertDatabaseHas('question_sets', [
            'id'     => $questionSet->id,
            'status' => 'processing',
            // Total sementara tetap 5 (jumlah soal aktual) sampai job selesai.
            'total_questions' => 5,
        ]);
    }

    public function test_decreasing_total_questions_is_rejected(): void
    {
        Queue::fake();

        $user        = $this->makeVerifiedUser();
        $questionSet = $this->makeQuestionSet($user, ['total_questions' => 5]);
        $this->makeQuestions($questionSet, 5);

        $response = $this->actingAs($user)->put("/bank-soal/{$questionSet->id}", [
            'title'           => $questionSet->title,
            'subject'         => $questionSet->subject,
            'grade'           => $questionSet->grade,
            'topic'           => $questionSet->topic,
            'question_type'   => $questionSet->question_type,
            'difficulty'      => $questionSet->difficulty,
            'curriculum'      => 'merdeka',
            'assessment_type' => 'reguler',
            'total_questions' => 3,
        ]);

        $response->assertSessionHasErrors('total_questions');
        Queue::assertNotPushed(AddQuestionsJob::class);
    }

    public function test_increasing_total_questions_blocked_when_quota_exhausted(): void
    {
        Queue::fake();

        $user = $this->makeVerifiedUser([
            'quota_used_this_month' => 10, // free plan limit
        ]);
        $questionSet = $this->makeQuestionSet($user, ['total_questions' => 5]);
        $this->makeQuestions($questionSet, 5);

        $response = $this->actingAs($user)->put("/bank-soal/{$questionSet->id}", [
            'title'           => $questionSet->title,
            'subject'         => $questionSet->subject,
            'grade'           => $questionSet->grade,
            'topic'           => $questionSet->topic,
            'question_type'   => $questionSet->question_type,
            'difficulty'      => $questionSet->difficulty,
            'curriculum'      => 'merdeka',
            'assessment_type' => 'reguler',
            'total_questions' => 8,
        ]);

        $response->assertSessionHasErrors('quota');
        Queue::assertNotPushed(AddQuestionsJob::class);
    }

    // ── Hapus soal individual ────────────────────────────────────────────────

    public function test_owner_can_delete_a_single_question(): void
    {
        $user        = $this->makeVerifiedUser();
        $questionSet = $this->makeQuestionSet($user, ['total_questions' => 5]);
        $this->makeQuestions($questionSet, 5);
        $questionToDelete = $questionSet->questions()->first();

        $response = $this->actingAs($user)
            ->delete("/bank-soal/{$questionSet->id}/questions/{$questionToDelete->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted('questions', ['id' => $questionToDelete->id]);
        $this->assertEquals(4, $questionSet->fresh()->questions()->count());

        // total_questions harus otomatis mengikuti jumlah soal aktual.
        $this->assertDatabaseHas('question_sets', [
            'id'              => $questionSet->id,
            'total_questions' => 4,
        ]);
    }

    public function test_cannot_delete_the_last_remaining_question(): void
    {
        $user        = $this->makeVerifiedUser();
        $questionSet = $this->makeQuestionSet($user, ['total_questions' => 1]);
        $this->makeQuestions($questionSet, 1);
        $lastQuestion = $questionSet->questions()->first();

        $response = $this->actingAs($user)
            ->delete("/bank-soal/{$questionSet->id}/questions/{$lastQuestion->id}");

        $response->assertSessionHasErrors('question');
        $this->assertDatabaseHas('questions', ['id' => $lastQuestion->id, 'deleted_at' => null]);
    }

    public function test_user_cannot_delete_question_from_other_users_question_set(): void
    {
        $owner       = $this->makeVerifiedUser();
        $intruder    = $this->makeVerifiedUser();
        $questionSet = $this->makeQuestionSet($owner, ['total_questions' => 5]);
        $this->makeQuestions($questionSet, 5);
        $question = $questionSet->questions()->first();

        $response = $this->actingAs($intruder)
            ->delete("/bank-soal/{$questionSet->id}/questions/{$question->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'deleted_at' => null]);
    }

    public function test_cannot_delete_question_belonging_to_a_different_question_set(): void
    {
        $user         = $this->makeVerifiedUser();
        $questionSetA = $this->makeQuestionSet($user, ['total_questions' => 5]);
        $questionSetB = $this->makeQuestionSet($user, ['total_questions' => 5]);
        $this->makeQuestions($questionSetA, 5);
        $this->makeQuestions($questionSetB, 5);
        $questionFromB = $questionSetB->questions()->first();

        // Coba hapus soal milik set B lewat URL set A — harus ditolak.
        $response = $this->actingAs($user)
            ->delete("/bank-soal/{$questionSetA->id}/questions/{$questionFromB->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('questions', ['id' => $questionFromB->id, 'deleted_at' => null]);
    }
}