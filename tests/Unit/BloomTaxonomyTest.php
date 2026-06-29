<?php

namespace Tests\Unit;

use App\Services\AI\AbstractAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BloomTaxonomyTest extends TestCase
{
    private function getPrompt(array $data): string
    {
        // Buat anonymous class untuk akses protected method
        $service = new class extends AbstractAIService {
            public function generateQuestions(array $data): array { return []; }
            public function exposeBuildPrompt(array $data): string
            {
                return $this->buildPrompt($data);
            }
        };

        return $service->exposeBuildPrompt($data);
    }

    private function baseData(): array
    {
        return [
            'subject'         => 'Biologi',
            'grade'           => 'Kelas 9 SMP',
            'topic'           => 'Sel Tumbuhan',
            'question_type'   => 'multiple_choice',
            'difficulty'      => 'sedang',
            'total_questions' => 5,
            'material_text'   => null,
            'material_image'  => null,
            'image_description' => null,
        ];
    }

    public function test_mudah_contains_bloom_c1_c2(): void
    {
        $prompt = $this->getPrompt(array_merge($this->baseData(), ['difficulty' => 'mudah']));

        $this->assertStringContainsString('C1–C2', $prompt);
        $this->assertStringContainsString('Mengingat', $prompt);
        $this->assertStringContainsString('Memahami', $prompt);
    }

    public function test_sedang_contains_bloom_c3_c4(): void
    {
        $prompt = $this->getPrompt($this->baseData());

        $this->assertStringContainsString('C3–C4', $prompt);
        $this->assertStringContainsString('Mengaplikasikan', $prompt);
        $this->assertStringContainsString('Menganalisis', $prompt);
    }

    public function test_sulit_contains_bloom_c5_c6(): void
    {
        $prompt = $this->getPrompt(array_merge($this->baseData(), ['difficulty' => 'sulit']));

        $this->assertStringContainsString('C5–C6', $prompt);
        $this->assertStringContainsString('Mengevaluasi', $prompt);
        $this->assertStringContainsString('Mencipta', $prompt);
    }

    public function test_grade_context_included_in_prompt(): void
    {
        $prompt = $this->getPrompt($this->baseData());

        $this->assertStringContainsString('14-15 tahun', $prompt);
        $this->assertStringContainsString('SMP', $prompt);
    }

    public function test_sd_grade_has_correct_age_context(): void
    {
        $prompt = $this->getPrompt(array_merge($this->baseData(), ['grade' => 'Kelas 4 SD']));

        $this->assertStringContainsString('9-10 tahun', $prompt);
        $this->assertStringContainsString('SD', $prompt);
    }

    public function test_material_text_included_when_provided(): void
    {
        $data   = array_merge($this->baseData(), ['material_text' => 'Sel tumbuhan memiliki dinding sel.']);
        $prompt = $this->getPrompt($data);

        $this->assertStringContainsString('Sel tumbuhan memiliki dinding sel.', $prompt);
        $this->assertStringContainsString('MATERI SUMBER', $prompt);
    }

    public function test_anti_hallucination_instructions_present_with_material(): void
    {
        $data   = array_merge($this->baseData(), ['material_text' => 'Materi biologi.']);
        $prompt = $this->getPrompt($data);

        $this->assertStringContainsString('ANTI HALLUCINATION', $prompt);
        $this->assertStringContainsString('source_paragraph', $prompt);
    }

    public function test_essay_prompt_has_no_options(): void
    {
        $data   = array_merge($this->baseData(), ['question_type' => 'essay']);
        $prompt = $this->getPrompt($data);

        $this->assertStringNotContainsString('option_a', $prompt);
        $this->assertStringNotContainsString('option_b', $prompt);
        $this->assertStringContainsString('correct_answer', $prompt);
    }

    public function test_multiple_choice_prompt_has_four_options(): void
    {
        $prompt = $this->getPrompt($this->baseData());

        $this->assertStringContainsString('option_a', $prompt);
        $this->assertStringContainsString('option_b', $prompt);
        $this->assertStringContainsString('option_c', $prompt);
        $this->assertStringContainsString('option_d', $prompt);
    }

    public function test_material_text_truncated_to_limit(): void
    {
        $longText = str_repeat('a', 10000);
        $data     = array_merge($this->baseData(), ['material_text' => $longText]);
        $prompt   = $this->getPrompt($data);

        // Prompt tidak boleh mengandung lebih dari limit karakter material
        $this->assertLessThan(20000, strlen($prompt));
    }
}
