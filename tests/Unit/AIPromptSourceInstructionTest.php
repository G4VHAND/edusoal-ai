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

    public function test_prompt_without_material_json_example_is_no_longer_the_old_generic_placeholder(): void
    {
        // Root cause bug yang ditemukan: AI meniru pola dari CONTOH KONKRET
        // di JSON template lebih kuat daripada instruksi prosa di atasnya.
        // Contoh lama ini statis untuk kedua kondisi (ada materi / tidak),
        // jadi walau instruksi prosa sudah diperluas, AI tetap ikut generik
        // karena mencontoh JSON example ini. Pastikan sudah tidak ada lagi.
        $prompt = $this->buildPrompt($this->baseData());

        $this->assertStringNotContainsString(
            '"source_paragraph": "Kutipan singkat dari materi sumber yang menjadi dasar soal ini."',
            $prompt
        );
    }

    public function test_prompt_with_material_json_example_still_shows_quote_placeholder(): void
    {
        // Sebaliknya, kalau ADA materi upload, contoh JSON tetap harus
        // mengarahkan AI untuk mengutip dari materi (bukan ikut berubah
        // jadi contoh jurnal/YouTube yang tidak relevan di kasus ini).
        $prompt = $this->buildPrompt($this->baseData(['material_text' => 'Contoh isi materi pembelajaran.']));

        $this->assertStringContainsString(
            '"source_paragraph": "Kutipan singkat (1-2 kalimat) dari materi sumber yang menjadi dasar soal ini."',
            $prompt
        );
        $this->assertStringNotContainsString('jurnal/ebook/artikel/video', $prompt);
    }

    public function test_prompt_json_example_block_is_valid_json(): void
    {
        // Regresi untuk bug nyata yang pernah terjadi: contoh nilai
        // (source_reference/source_paragraph) sempat mengandung tanda kutip
        // dua (") mentah di dalamnya, yang MERUSAK struktur contoh JSON yang
        // dikirim ke AI — akibatnya AI ikut bingung dan membalas JSON tidak
        // valid (error "Format JSON dari AI tidak memiliki key questions").
        // Pastikan blok contoh JSON di akhir prompt selalu valid JSON,
        // untuk kombinasi tipe soal & kondisi materi.
        foreach (['multiple_choice', 'essay'] as $questionType) {
            foreach ([false, true] as $withMaterial) {
                $data = $this->baseData(array_merge(
                    ['question_type' => $questionType],
                    $withMaterial ? ['material_text' => 'Contoh materi pembelajaran singkat.'] : []
                ));
                $prompt = $this->buildPrompt($data);

                $jsonStart = strpos($prompt, "\n{\n");
                $this->assertNotFalse($jsonStart, "Blok contoh JSON tidak ditemukan di prompt ({$questionType}).");

                $jsonBlock = trim(substr($prompt, $jsonStart));
                $decoded = json_decode($jsonBlock, true);

                $this->assertNotNull(
                    $decoded,
                    "Contoh JSON di prompt tidak valid untuk {$questionType} (withMaterial=".($withMaterial ? 'ya' : 'tidak').'): '.json_last_error_msg()."\n\n".$jsonBlock
                );
                $this->assertArrayHasKey('questions', $decoded);
            }
        }
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
