<?php

namespace Tests\Feature\Jobs;

use App\Jobs\GenerateQuestionsJob;
use App\Models\QuestionSet;
use App\Models\User;
use App\Services\AI\QuestionGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GenerateQuestionsJobSourceReferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.providers.gemini.key' => 'fake-gemini-key',
            'ai.providers.gemini.model' => 'gemini-fake',
            'ai.providers.gemini.retry' => 1,
            'ai.providers.gemini.retry_sleep' => 0,
            'ai.providers.gemini.timeout' => 30,
        ]);
    }

    private function fakeGeminiResponse(array $payload): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($payload)],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    public function test_source_reference_is_saved_from_ai_response_when_generating_new_set(): void
    {
        $user = User::factory()->create([
            'role' => 'individual',
            'email_verified_at' => now(),
            'quota_used_this_month' => 0,
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        $questionSet = QuestionSet::create([
            'user_id' => $user->id,
            'title' => 'Test',
            'subject' => 'Biologi',
            'grade' => 'Kelas 11 SMA',
            'topic' => 'Sistem Sirkulasi',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'total_questions' => 1,
            'ai_provider' => 'gemini',
            'status' => 'pending',
        ]);

        $this->fakeGeminiResponse([
            'source_reference' => 'Buku Paket Biologi Kelas 11 Kurikulum Merdeka (Kemdikbud)',
            'questions' => [[
                'question_text' => 'Apa fungsi jantung?',
                'option_a' => 'Memompa darah',
                'option_b' => 'Mencerna makanan',
                'option_c' => 'Menyaring udara',
                'option_d' => 'Menghasilkan hormon',
                'correct_answer' => 'A',
                'explanation' => 'Jantung memompa darah ke seluruh tubuh.',
                'source_paragraph' => 'Pengetahuan umum Biologi',
                'needs_image' => false,
                'image_recommendation' => null,
            ]],
        ]);

        (new GenerateQuestionsJob($questionSet->id, [
            'subject' => 'Biologi',
            'grade' => 'Kelas 11 SMA',
            'topic' => 'Sistem Sirkulasi',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'total_questions' => 1,
        ]))->handle(app(QuestionGenerationService::class));

        $questionSet->refresh();

        $this->assertEquals('completed', $questionSet->status);
        $this->assertEquals(
            'Buku Paket Biologi Kelas 11 Kurikulum Merdeka (Kemdikbud)',
            $questionSet->source_reference
        );
    }

    public function test_generation_still_succeeds_when_ai_forgets_source_reference(): void
    {
        // Kalau AI tidak menyertakan field "source_reference" sama sekali,
        // proses generate TIDAK boleh gagal — cukup null, tidak fatal.
        $user = User::factory()->create([
            'role' => 'individual',
            'email_verified_at' => now(),
            'quota_used_this_month' => 0,
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        $questionSet = QuestionSet::create([
            'user_id' => $user->id,
            'title' => 'Test',
            'subject' => 'Matematika',
            'grade' => 'Kelas 9 SMP',
            'topic' => 'Aljabar',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'total_questions' => 1,
            'ai_provider' => 'gemini',
            'status' => 'pending',
        ]);

        $this->fakeGeminiResponse([
            'questions' => [[
                'question_text' => 'Berapa 2 + 2?',
                'option_a' => '3', 'option_b' => '4', 'option_c' => '5', 'option_d' => '6',
                'correct_answer' => 'B',
            ]],
        ]);

        (new GenerateQuestionsJob($questionSet->id, [
            'subject' => 'Matematika',
            'grade' => 'Kelas 9 SMP',
            'topic' => 'Aljabar',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'total_questions' => 1,
        ]))->handle(app(QuestionGenerationService::class));

        $questionSet->refresh();

        $this->assertEquals('completed', $questionSet->status);
        $this->assertNull($questionSet->source_reference);
    }

    public function test_generic_pengetahuan_umum_source_reference_is_sanitized_end_to_end(): void
    {
        // Kasus nyata yang pernah terjadi: walau prompt sudah diinstruksikan,
        // AI kadang tetap balas frasa generik. Pastikan job ini (jalur nyata
        // yang dipakai user, bukan cuma unit test service-nya) benar-benar
        // menyaring itu sebelum disimpan ke database.
        $user = User::factory()->create([
            'role' => 'individual',
            'email_verified_at' => now(),
            'quota_used_this_month' => 0,
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        $questionSet = QuestionSet::create([
            'user_id' => $user->id,
            'title' => 'Test',
            'subject' => 'Biologi',
            'grade' => 'Kelas 11 SMA',
            'topic' => 'Sistem Sirkulasi',
            'question_type' => 'essay',
            'difficulty' => 'sedang',
            'total_questions' => 1,
            'ai_provider' => 'gemini',
            'status' => 'pending',
        ]);

        $this->fakeGeminiResponse([
            'source_reference' => 'Pengetahuan umum Biologi',
            'questions' => [[
                'question_text' => 'Sebutkan tiga jenis sel darah.',
                'correct_answer' => 'Eritrosit, leukosit, trombosit.',
                'source_paragraph' => 'Pengetahuan umum Biologi',
            ]],
        ]);

        (new GenerateQuestionsJob($questionSet->id, [
            'subject' => 'Biologi',
            'grade' => 'Kelas 11 SMA',
            'topic' => 'Sistem Sirkulasi',
            'question_type' => 'essay',
            'difficulty' => 'sedang',
            'total_questions' => 1,
        ]))->handle(app(QuestionGenerationService::class));

        $questionSet->refresh();

        $this->assertEquals('completed', $questionSet->status);
        $this->assertEquals('Konsep dasar Biologi tingkat Kelas 11 SMA', $questionSet->source_reference);
        $this->assertEquals(
            'Konsep dasar Biologi tingkat Kelas 11 SMA',
            $questionSet->questions()->first()->source_paragraph
        );
    }
}
