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

    public function test_clean_text_preserves_bold_markers_for_later_rendering(): void
    {
        // '**...**' TIDAK lagi dihapus — dipertahankan sebagai penanda bold
        // yang nanti diproses jadi huruf tebal sungguhan oleh TextFormatter
        // saat ditampilkan di web / diexport ke PDF/Word.
        $this->assertEquals('**Jawaban benar**', $this->service->cleanText('**Jawaban benar**'));
    }

    public function test_clean_text_strips_code_fence_and_trims_whitespace(): void
    {
        $this->assertEquals('kode', $this->service->cleanText('```kode```'));
        $this->assertEquals('Halo dunia', $this->service->cleanText('  Halo dunia  '));
    }

    public function test_clean_text_returns_null_for_null_input(): void
    {
        $this->assertNull($this->service->cleanText(null));
    }

    // ── sanitizeSourceText — jaring pengaman anti "pengetahuan umum" ────────────

    public function test_sanitize_source_text_replaces_generic_pengetahuan_umum_phrase(): void
    {
        // Kasus nyata yang pernah terjadi: AI tetap balas "Pengetahuan umum
        // Biologi" walau prompt sudah diinstruksikan untuk tidak begitu.
        $result = $this->service->sanitizeSourceText('Pengetahuan umum Biologi', 'Biologi', 'Kelas 11 SMA');

        $this->assertEquals('Konsep dasar Biologi tingkat Kelas 11 SMA', $result);
    }

    public function test_sanitize_source_text_is_case_insensitive(): void
    {
        $result = $this->service->sanitizeSourceText('PENGETAHUAN UMUM Fisika', 'Fisika', 'Kelas 10 SMA');

        $this->assertEquals('Konsep dasar Fisika tingkat Kelas 10 SMA', $result);
    }

    public function test_sanitize_source_text_leaves_specific_source_untouched(): void
    {
        $specific = 'Jurnal Pendidikan Sains Indonesia — topik Sistem Peredaran Darah';

        $result = $this->service->sanitizeSourceText($specific, 'Biologi', 'Kelas 11 SMA');

        $this->assertEquals($specific, $result);
    }

    public function test_sanitize_source_text_passes_through_null_and_empty(): void
    {
        $this->assertNull($this->service->sanitizeSourceText(null, 'Biologi', 'Kelas 11 SMA'));
        $this->assertEquals('', $this->service->sanitizeSourceText('', 'Biologi', 'Kelas 11 SMA'));
    }

    public function test_create_questions_sanitizes_generic_source_paragraph(): void
    {
        $user = User::factory()->create(['role' => 'individual']);
        $questionSet = QuestionSet::create([
            'user_id' => $user->id,
            'title' => 'Test',
            'subject' => 'Biologi',
            'grade' => 'Kelas 11 SMA',
            'topic' => 'Sistem Sirkulasi',
            'question_type' => 'essay',
            'difficulty' => 'sedang',
            'total_questions' => 1,
            'status' => 'completed',
        ]);

        $this->service->createQuestions($questionSet, [
            [
                'question_text' => 'Jelaskan fungsi jantung.',
                'correct_answer' => 'Jantung memompa darah.',
                'source_paragraph' => 'Pengetahuan umum Biologi',
            ],
        ]);

        $question = $questionSet->questions()->first();

        // Bukan lagi frasa generik mentah dari AI — sudah diganti oleh
        // safety net jadi fallback yang lebih informatif.
        $this->assertEquals('Konsep dasar Biologi tingkat Kelas 11 SMA', $question->source_paragraph);
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
