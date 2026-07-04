<?php

namespace Tests\Unit;

use App\Models\QuestionSet;
use App\Models\User;
use App\Services\AI\QuestionGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuestionGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuestionGenerationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new QuestionGenerationService;

        // Hindari retry/sleep asli Http::retry() bikin test lambat.
        config([
            'ai.providers.gemini.key' => 'fake-gemini-key',
            'ai.providers.gemini.retry' => 1,
            'ai.providers.groq.key' => 'fake-groq-key',
        ]);
    }

    // ── parseAiJson ───────────────────────────────────────────────────────────

    public function test_parse_ai_json_handles_markdown_code_fence(): void
    {
        $raw = "```json\n".json_encode(['questions' => [['question_text' => 'Apa itu 1+1?']]])."\n```";

        $result = $this->service->parseAiJson($raw);

        $this->assertCount(1, $result['questions']);
        $this->assertEquals('Apa itu 1+1?', $result['questions'][0]['question_text']);
    }

    public function test_parse_ai_json_handles_plain_json(): void
    {
        $raw = json_encode(['questions' => [['question_text' => 'Soal A']]]);

        $result = $this->service->parseAiJson($raw);

        $this->assertCount(1, $result['questions']);
    }

    public function test_parse_ai_json_throws_on_invalid_json(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/JSON dari AI tidak valid/');

        $this->service->parseAiJson('{ ini bukan json valid');
    }

    public function test_parse_ai_json_throws_when_questions_key_missing(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/tidak memiliki key "questions"/');

        $this->service->parseAiJson(json_encode(['foo' => 'bar']));
    }

    public function test_parse_ai_json_throws_when_questions_array_empty(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/array soal kosong/');

        $this->service->parseAiJson(json_encode(['questions' => []]));
    }

    // ── cleanText ─────────────────────────────────────────────────────────────

    public function test_clean_text_strips_markdown_characters(): void
    {
        $this->assertEquals('Jawaban benar', $this->service->cleanText('**Jawaban benar**'));
        $this->assertEquals('kode', $this->service->cleanText('```kode```'));
    }

    public function test_clean_text_returns_null_for_null_input(): void
    {
        $this->assertNull($this->service->cleanText(null));
    }

    // ── createQuestions ───────────────────────────────────────────────────────

    public function test_create_questions_appends_without_deleting_existing(): void
    {
        $user = User::factory()->create(['role' => 'individual']);
        $questionSet = QuestionSet::create([
            'user_id' => $user->id,
            'title' => 'Test',
            'subject' => 'Matematika',
            'grade' => 'Kelas 9 SMP',
            'topic' => 'Aljabar',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'total_questions' => 2,
            'status' => 'completed',
        ]);

        $this->service->createQuestions($questionSet, [
            ['question_text' => 'Soal lama 1', 'correct_answer' => 'A'],
        ]);
        $this->assertEquals(1, $questionSet->questions()->count());

        $this->service->createQuestions($questionSet, [
            ['question_text' => 'Soal baru 1', 'correct_answer' => 'B'],
            ['question_text' => 'Soal baru 2', 'correct_answer' => 'C'],
        ]);

        // 1 lama + 2 baru = 3, bukan menimpa yang lama.
        $this->assertEquals(3, $questionSet->questions()->count());
        $this->assertDatabaseHas('questions', ['question_text' => 'Soal lama 1']);
    }

    // ── generateWithFallback ──────────────────────────────────────────────────

    public function test_fallback_uses_gemini_first_without_calling_groq_when_gemini_succeeds(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => '{"questions":[{"question_text":"Q1"}]}']]]]],
            ], 200),
            'api.groq.com/*' => Http::response([], 500),
        ]);

        $result = $this->service->generateWithFallback($this->payload());

        $this->assertFalse($result['used_fallback']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.groq.com'));
    }

    public function test_fallback_to_groq_when_gemini_fails(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'server error'], 500),
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"questions":[{"question_text":"Q1"}]}']]],
            ], 200),
        ]);

        $result = $this->service->generateWithFallback($this->payload());

        $this->assertTrue($result['used_fallback']);
        $this->assertEquals(
            '{"questions":[{"question_text":"Q1"}]}',
            $result['response']['raw_result']
        );
    }

    public function test_fallback_throws_when_both_providers_fail(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'gemini down'], 500),
            'api.groq.com/*' => Http::response(['error' => 'groq down'], 500),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Groq API Error/');

        $this->service->generateWithFallback($this->payload());
    }

    public function test_fallback_does_not_try_groq_when_image_present_and_gemini_fails(): void
    {
        // Groq tidak support Vision — kalau ada gambar materi, tidak boleh
        // fallback ke Groq sama sekali walaupun Gemini gagal.
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'gemini down'], 500),
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"questions":[{"question_text":"Q1"}]}']]],
            ], 200),
        ]);

        try {
            $this->service->generateWithFallback($this->payload(), hasImage: true);
            $this->fail('Seharusnya throw exception karena Gemini gagal dan tidak boleh fallback ke Groq.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Gemini API Error', $e->getMessage());
        }

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.groq.com'));
    }

    private function payload(): array
    {
        return [
            'subject' => 'Matematika',
            'grade' => 'Kelas 9 SMP',
            'topic' => 'Aljabar',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'curriculum' => 'merdeka',
            'assessment_type' => 'reguler',
            'total_questions' => 5,
        ];
    }
}
