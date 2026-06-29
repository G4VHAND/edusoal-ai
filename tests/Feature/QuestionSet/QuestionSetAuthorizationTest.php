<?php

namespace Tests\Feature\QuestionSet;

use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $questionSet = $this->makeQuestionSet($user);

        $response = $this->actingAs($user)->put("/bank-soal/{$questionSet->id}", [
            'title'           => 'Judul Baru',
            'subject'         => 'Matematika',
            'grade'           => 'Kelas 9 SMP',
            'topic'           => 'Aljabar',
            'question_type'   => 'multiple_choice',
            'difficulty'      => 'sulit',
            'total_questions' => 10,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('question_sets', [
            'id'    => $questionSet->id,
            'title' => 'Judul Baru',
        ]);
    }
}
