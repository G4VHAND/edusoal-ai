<?php

namespace App\Services\AI;

use App\Models\Question;
use App\Models\QuestionSet;
use Illuminate\Support\Facades\Cache;

/**
 * Logic generate soal yang dipakai bersama oleh GenerateQuestionsJob
 * (bikin set soal baru) dan AddQuestionsJob (tambah soal ke set yang
 * sudah ada). Diekstrak supaya kedua job tidak duplikasi ~70% kode yang
 * sama (fallback provider, parsing JSON, pembersihan teks, dsb).
 */
class QuestionGenerationService
{
    /**
     * Coba generate soal, otomatis fallback ke provider berikutnya kalau
     * provider sebelumnya gagal.
     *
     * @param  array<string, mixed>  $payload
     * @return array{response: array, used_fallback: bool}
     *
     * @throws \Exception jika SEMUA provider gagal — exception dari
     *                     provider TERAKHIR yang dicoba.
     */
    public function generateWithFallback(array $payload, bool $hasImage = false): array
    {
        // Groq tidak support Vision — kalau ada gambar materi, cuma coba Gemini.
        $providers = $hasImage ? ['gemini'] : ['gemini', 'groq'];
        $lastError = null;

        foreach ($providers as $index => $providerName) {
            try {
                $service  = AIServiceFactory::make($providerName);
                $response = $service->generateQuestions($payload);

                return [
                    'response'      => $response,
                    'used_fallback' => $index > 0,
                ];
            } catch (\Exception $e) {
                $lastError = $e;
                continue; // coba provider berikutnya
            }
        }

        throw $lastError ?? new \Exception('Semua provider AI gagal merespons.');
    }

    /**
     * Parse hasil mentah AI (biasanya dibungkus ```json ... ```) jadi array.
     *
     * @throws \Exception jika JSON invalid atau tidak punya key "questions".
     */
    public function parseAiJson(string $raw): array
    {
        $clean = trim($raw);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', $clean);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON dari AI tidak valid: ' . json_last_error_msg());
        }

        if (! isset($decoded['questions']) || ! is_array($decoded['questions'])) {
            throw new \Exception('Format JSON dari AI tidak memiliki key "questions".');
        }

        if (count($decoded['questions']) === 0) {
            throw new \Exception('AI mengembalikan array soal kosong.');
        }

        return $decoded;
    }

    public function cleanText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return trim(str_replace(['**', '*', '```'], '', $text));
    }

    /**
     * Buat baris Question dari hasil decode JSON AI. SELALU append —
     * tidak pernah menghapus/menimpa soal yang sudah ada di question set.
     */
    public function createQuestions(QuestionSet $questionSet, array $decodedQuestions, ?string $imageDescription = null): void
    {
        foreach ($decodedQuestions as $item) {
            Question::create([
                'question_set_id'      => $questionSet->id,
                'question_text'        => $item['question_text'] ?? $item['question'] ?? '',
                'option_a'             => $item['option_a'] ?? null,
                'option_b'             => $item['option_b'] ?? null,
                'option_c'             => $item['option_c'] ?? null,
                'option_d'             => $item['option_d'] ?? null,
                'correct_answer'       => $this->cleanText($item['correct_answer'] ?? null),
                'explanation'          => $this->cleanText($item['explanation'] ?? null),
                'source_paragraph'     => $this->cleanText($item['source_paragraph'] ?? null),
                'needs_image'          => (bool) ($item['needs_image'] ?? false),
                'image_recommendation' => $this->cleanText($item['image_recommendation'] ?? null),
                'image_description'    => $imageDescription,
            ]);
        }
    }

    public function clearDashboardCache(int $userId): void
    {
        Cache::forget("dashboard:{$userId}:all");
        Cache::forget("dashboard:{$userId}:7days");
        Cache::forget("dashboard:{$userId}:30days");
        Cache::forget("dashboard:{$userId}:year");
    }
}
