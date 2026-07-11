<?php

namespace Tests\Unit;

use App\Services\AI\GeminiService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Memastikan instruksi sumber referensi (source_reference & source_paragraph)
 * yang dikirim ke AI benar-benar sudah diperluas — tidak lagi cuma
 * "Pengetahuan umum [mapel]" saat generate murni dari topik (tanpa upload
 * materi/gambar), melainkan minta AI menyebut jenis sumber konkret
 * (buku, jurnal, ebook, artikel web, atau video pembelajaran).
 */
class AIPromptSourceInstructionTest extends TestCase
{
    private function buildPrompt(array $data): string
    {
        $service = new GeminiService;
        $method = new ReflectionMethod($service, 'buildPrompt');
        $method->setAccessible(true);

        return $method->invoke($service, $data);
    }

    private function baseData(array $overrides = []): array
    {
        return array_merge([
            'subject' => 'Biologi',
            'grade' => 'Kelas 11 SMA',
            'topic' => 'Sistem Sirkulasi',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'total_questions' => 5,
            'curriculum' => 'merdeka',
            'assessment_type' => 'reguler',
        ], $overrides);
    }

    // ── Tanpa materi/gambar upload (generate murni dari topik) ──────────────

    public function test_prompt_without_material_offers_multiple_source_types(): void
    {
        $prompt = $this->buildPrompt($this->baseData());

        $this->assertStringContainsString('Jurnal ilmiah', $prompt);
        $this->assertStringContainsString('Ebook atau modul pembelajaran daring', $prompt);
        $this->assertStringContainsString('Video pembelajaran', $prompt);
        $this->assertStringContainsString('YouTube', $prompt);
    }

    public function test_prompt_without_material_forbids_fabricated_links(): void
    {
        $prompt = $this->buildPrompt($this->baseData());

        // Guardrail anti-hallucination: AI dilarang mengarang URL/DOI persis.
        $this->assertStringContainsString('JANGAN menulis link/URL', $prompt);
    }

    public function test_prompt_without_material_no_longer_uses_flat_generic_fallback(): void
    {
        $prompt = $this->buildPrompt($this->baseData());

        // Tidak boleh lagi ada instruksi literal menyuruh AI menulis
        // "Pengetahuan umum [mata pelajaran]" begitu saja tanpa konteks.
        $this->assertStringNotContainsString('dengan "Pengetahuan umum [mata pelajaran]"', $prompt);
    }

    public function test_prompt_without_material_fallback_uses_actual_subject_and_grade(): void
    {
        $prompt = $this->buildPrompt($this->baseData(['subject' => 'Fisika', 'grade' => 'Kelas 10 SMA']));

        // Fallback generik (kalau AI tidak yakin ada sumber spesifik) tetap
        // menyebut mapel & kelas yang sesungguhnya, bukan placeholder mentah.
        $this->assertStringContainsString('Konsep dasar Fisika tingkat Kelas 10 SMA', $prompt);
    }

    // ── Dengan materi upload — tidak boleh berubah (tetap kutipan materi) ───

    public function test_prompt_with_material_still_requires_verbatim_quote_from_source(): void
    {
        $prompt = $this->buildPrompt($this->baseData([
            'material_text' => 'Jantung memompa darah ke seluruh tubuh melalui pembuluh darah.',
        ]));

        $this->assertStringContainsString('kutipan singkat', $prompt);
        $this->assertStringContainsString('Materi/dokumen yang diunggah pengguna', $prompt);
        // Tidak seharusnya menawarkan jurnal/YouTube kalau materi sudah ada —
        // sumbernya sudah pasti dari materi yang diupload.
        $this->assertStringNotContainsString('Video pembelajaran (mis. channel YouTube', $prompt);
    }
}
