<?php

namespace Tests\Feature\QuestionSet;

use App\Jobs\GenerateQuestionsJob;
use App\Models\QuestionSet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerateSoalTest extends TestCase
{
    use RefreshDatabase;

    private function makeVerifiedUser(array $attrs = []): User
    {
        return User::factory()->create([
            'email_verified_at'     => now(),
            'role'                  => 'individual',
            'quota_used_this_month' => 0,
            'quota_reset_at'        => now()->addMonth(),
            'is_active'             => true,
            ...$attrs,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title'           => 'UTS Matematika',
            'subject'         => 'Matematika',
            'grade'           => 'Kelas 9 SMP',
            'topic'           => 'Persamaan Linear',
            'question_type'   => 'multiple_choice',
            'difficulty'      => 'sedang',
            'curriculum'      => 'merdeka',
            'assessment_type' => 'reguler',
            'total_questions' => 5,
            'ai_provider'     => 'gemini',
        ], $overrides);
    }

    public function test_generate_soal_page_accessible_for_verified_user(): void
    {
        $user     = $this->makeVerifiedUser();
        $response = $this->actingAs($user)->get('/generate-soal');

        $response->assertOk();
    }

    public function test_generate_soal_dispatches_job_to_queue(): void
    {
        Queue::fake();

        $user     = $this->makeVerifiedUser();
        $response = $this->actingAs($user)->post('/generate-soal', $this->validPayload());

        Queue::assertPushed(GenerateQuestionsJob::class);
        $response->assertRedirect();
    }

    public function test_generate_soal_creates_question_set_with_pending_status(): void
    {
        Queue::fake();

        $user = $this->makeVerifiedUser();
        $this->actingAs($user)->post('/generate-soal', $this->validPayload());

        $this->assertDatabaseHas('question_sets', [
            'user_id' => $user->id,
            'title'   => 'UTS Matematika',
            'status'  => 'pending',
        ]);
    }

    public function test_generate_soal_redirects_to_show_page(): void
    {
        Queue::fake();

        $user     = $this->makeVerifiedUser();
        $response = $this->actingAs($user)->post('/generate-soal', $this->validPayload());

        $questionSet = QuestionSet::where('user_id', $user->id)->first();
        $response->assertRedirect("/bank-soal/{$questionSet->id}");
    }

    public function test_generate_soal_fails_with_invalid_grade(): void
    {
        Queue::fake();

        $user     = $this->makeVerifiedUser();
        $response = $this->actingAs($user)->post('/generate-soal', $this->validPayload([
            'grade' => 'Kelas 99 Tidak Ada',
        ]));

        $response->assertSessionHasErrors('grade');
        Queue::assertNothingPushed();
    }

    public function test_generate_soal_fails_with_invalid_provider(): void
    {
        Queue::fake();

        $user     = $this->makeVerifiedUser();
        $response = $this->actingAs($user)->post('/generate-soal', $this->validPayload([
            'ai_provider' => 'openai',
        ]));

        $response->assertSessionHasErrors('ai_provider');
        Queue::assertNothingPushed();
    }

    public function test_generate_soal_fails_when_total_questions_exceeds_50(): void
    {
        Queue::fake();

        $user     = $this->makeVerifiedUser();
        $response = $this->actingAs($user)->post('/generate-soal', $this->validPayload([
            'total_questions' => 51,
        ]));

        $response->assertSessionHasErrors('total_questions');
        Queue::assertNothingPushed();
    }

    public function test_generate_soal_blocked_when_quota_exhausted(): void
    {
        Queue::fake();

        $user = $this->makeVerifiedUser(['quota_used_this_month' => 10]);

        $response = $this->actingAs($user)->post('/generate-soal', $this->validPayload());

        // Middleware CheckQuota harus memblokir request
        $response->assertSessionHasErrors('quota');
        Queue::assertNothingPushed();
    }

    public function test_status_endpoint_returns_json(): void
    {
        Queue::fake();

        $user = $this->makeVerifiedUser();
        $this->actingAs($user)->post('/generate-soal', $this->validPayload());

        $questionSet = QuestionSet::where('user_id', $user->id)->first();
        $response    = $this->actingAs($user)->getJson("/bank-soal/{$questionSet->id}/status");

        $response->assertOk();
        $response->assertJsonStructure(['status', 'has_questions', 'ai_error']);
    }

    public function test_unauthenticated_user_cannot_generate_soal(): void
    {
        $response = $this->post('/generate-soal', $this->validPayload());
        $response->assertRedirect('/login');
    }
}