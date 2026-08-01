<?php

namespace Tests\Feature\Question;

use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fitur edit manual per-soal (poin #6 masukan pembimbing) — guru bisa
 * memperbaiki langsung teks soal/jawaban/pembahasan tanpa perlu generate
 * ulang lewat AI.
 */
class QuestionEditTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'individual',
            'email_verified_at' => now(),
            'quota_used_this_month' => 0,
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ], $attrs));
    }

    private function makeQuestionSet(User $user, array $attrs = []): QuestionSet
    {
        return QuestionSet::create(array_merge([
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
    }

    // ── Otorisasi ────────────────────────────────────────────────────────────

    public function test_owner_can_view_edit_page(): void
    {
        $user = $this->makeUser();
        $questionSet = $this->makeQuestionSet($user);
        $question = Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Soal asli',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'A',
        ]);

        $response = $this->actingAs($user)->get(route('questions.edit', $question->id));

        $response->assertOk();
        $response->assertSee('Soal asli');
    }

    public function test_non_owner_cannot_view_or_edit_question(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $questionSet = $this->makeQuestionSet($owner);
        $question = Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Soal asli',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'A',
        ]);

        $this->actingAs($stranger)->get(route('questions.edit', $question->id))->assertForbidden();

        $this->actingAs($stranger)->put(route('questions.update', $question->id), [
            'question_text' => 'Diubah paksa',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'A',
        ])->assertForbidden();

        $this->assertEquals('Soal asli', $question->fresh()->question_text);
    }

    // ── Update multiple choice ───────────────────────────────────────────────

    public function test_owner_can_update_multiple_choice_question(): void
    {
        $user = $this->makeUser();
        $questionSet = $this->makeQuestionSet($user);
        $question = Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Soal lama',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'A',
            'explanation' => 'Pembahasan lama',
        ]);

        $response = $this->actingAs($user)->put(route('questions.update', $question->id), [
            'question_text' => 'Soal yang sudah diperbaiki',
            'option_a' => 'Opsi A baru',
            'option_b' => 'Opsi B baru',
            'option_c' => 'Opsi C baru',
            'option_d' => 'Opsi D baru',
            'correct_answer' => 'c',
            'explanation' => 'Pembahasan yang sudah diperbaiki',
        ]);

        $response->assertRedirect(route('bank-soal.show', $questionSet->id));
        $response->assertSessionHas('success');

        $question->refresh();
        $this->assertEquals('Soal yang sudah diperbaiki', $question->question_text);
        $this->assertEquals('Opsi C baru', $question->option_c);
        // Huruf jawaban dinormalisasi jadi kapital walau guru ketik huruf kecil.
        $this->assertEquals('C', $question->correct_answer);
        $this->assertEquals('Pembahasan yang sudah diperbaiki', $question->explanation);
    }

    public function test_multiple_choice_requires_all_four_options(): void
    {
        $user = $this->makeUser();
        $questionSet = $this->makeQuestionSet($user);
        $question = Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Soal lama',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'A',
        ]);

        $response = $this->actingAs($user)->put(route('questions.update', $question->id), [
            'question_text' => 'Soal baru',
            'option_a' => 'A',
            // option_b sengaja tidak dikirim
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
        ]);

        $response->assertSessionHasErrors('option_b');
        $this->assertEquals('Soal lama', $question->fresh()->question_text);
    }

    // ── Update essay ─────────────────────────────────────────────────────────

    public function test_owner_can_update_essay_question_without_options(): void
    {
        $user = $this->makeUser();
        $questionSet = $this->makeQuestionSet($user, ['question_type' => 'essay']);
        $question = Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Soal essay lama',
            'correct_answer' => 'Jawaban lama',
        ]);

        $response = $this->actingAs($user)->put(route('questions.update', $question->id), [
            'question_text' => 'Soal essay yang sudah diperbaiki',
            'correct_answer' => 'Jawaban yang sudah diperbaiki',
            'explanation' => 'Pembahasan baru',
        ]);

        $response->assertRedirect(route('bank-soal.show', $questionSet->id));

        $question->refresh();
        $this->assertEquals('Soal essay yang sudah diperbaiki', $question->question_text);
        $this->assertEquals('Jawaban yang sudah diperbaiki', $question->correct_answer);
    }

    public function test_update_trims_whitespace_via_clean_text(): void
    {
        $user = $this->makeUser();
        $questionSet = $this->makeQuestionSet($user, ['question_type' => 'essay']);
        $question = Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Lama',
            'correct_answer' => 'Lama',
        ]);

        $this->actingAs($user)->put(route('questions.update', $question->id), [
            'question_text' => '  Ada spasi di pinggir  ',
            'correct_answer' => 'Jawaban',
        ]);

        $this->assertEquals('Ada spasi di pinggir', $question->fresh()->question_text);
    }
    // ── UI: tombol jelas & popup konfirmasi/notifikasi ──────────────────────

    public function test_update_redirect_carries_success_flash_message(): void
    {
        $user = $this->makeUser();
        $questionSet = $this->makeQuestionSet($user, ['question_type' => 'essay']);
        $question = Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Soal lama',
            'correct_answer' => 'Jawaban lama',
        ]);

        $response = $this->actingAs($user)->put(route('questions.update', $question->id), [
            'question_text' => 'Soal baru',
            'correct_answer' => 'Jawaban baru',
        ]);

        $response->assertSessionHas('success', 'Soal berhasil diperbarui.');
    }

    public function test_detail_page_uses_confirmation_modal_not_native_confirm(): void
    {
        $user = $this->makeUser();
        $questionSet = $this->makeQuestionSet($user);
        $question = Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Soal A',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'A',
        ]);

        $response = $this->actingAs($user)->get(route('bank-soal.show', $questionSet->id));

        $response->assertOk();
        // Modal konfirmasi (bukan lagi native browser confirm()) harus ada.
        $response->assertSee("confirm-delete-question-{$question->id}", false);
        $response->assertDontSee('onclick="return confirm(', false);
    }
}
