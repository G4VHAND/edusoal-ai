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
     *                    provider TERAKHIR yang dicoba.
     */
    public function generateWithFallback(array $payload, bool $hasImage = false): array
    {
        // Groq tidak support Vision — kalau ada gambar materi, cuma coba Gemini.
        $providers = $hasImage ? ['gemini'] : ['gemini', 'groq'];
        $lastError = null;

        foreach ($providers as $index => $providerName) {
            try {
                $service = AIServiceFactory::make($providerName);
                $response = $service->generateQuestions($payload);

                return [
                    'response' => $response,
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
            throw new \Exception('JSON dari AI tidak valid: '.json_last_error_msg());
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

        // Catatan: dulu function ini menghapus '**' dan '*' sepenuhnya
        // (jadi penanda bold dari AI hilang begitu saja). Sekarang '**bold**'
        // SENGAJA dipertahankan sebagai penanda bold — akan diproses jadi
        // huruf tebal sungguhan saat ditampilkan/export lewat TextFormatter.
        // Yang dibersihkan di sini hanya sisa pembungkus kode block AI.
        return trim(str_replace('```', '', $text));
    }

    /**
     * Jaring pengaman untuk field sumber (source_paragraph / source_reference).
     *
     * Prompt sudah diinstruksikan supaya AI menyebut sumber yang lebih
     * spesifik (jurnal, ebook, video, dst.) daripada frasa generik
     * "pengetahuan umum" — tapi LLM tidak selalu 100% patuh ke instruksi
     * prompt, walau instruksinya sudah jelas dan contohnya sudah diubah.
     * Daripada terus-menerus bergantung pada kepatuhan AI, kalau responsnya
     * TETAP mengandung frasa generik itu, kita ganti otomatis di kode
     * dengan fallback yang lebih informatif (pakai data asli dari
     * question set, bukan tebakan AI) — supaya guru tidak pernah lihat
     * "Pengetahuan umum X" lagi di UI, apapun yang dibalas AI.
     */
    public function sanitizeSourceText(?string $text, string $subject, string $grade): ?string
    {
        if ($text === null || trim($text) === '') {
            return $text;
        }

        if (str_contains(mb_strtolower($text), 'pengetahuan umum')) {
            return "Konsep dasar {$subject} tingkat {$grade}";
        }

        return $text;
    }

    /**
     * Buat baris Question dari hasil decode JSON AI. SELALU append —
     * tidak pernah menghapus/menimpa soal yang sudah ada di question set.
     */
    public function createQuestions(QuestionSet $questionSet, array $decodedQuestions, ?string $imageDescription = null): void
    {
        foreach ($decodedQuestions as $item) {
            Question::create([
                'question_set_id' => $questionSet->id,
                'question_text' => $this->cleanText($item['question_text'] ?? $item['question'] ?? ''),
                'option_a' => $this->cleanText($item['option_a'] ?? null),
                'option_b' => $this->cleanText($item['option_b'] ?? null),
                'option_c' => $this->cleanText($item['option_c'] ?? null),
                'option_d' => $this->cleanText($item['option_d'] ?? null),
                'correct_answer' => $this->cleanText($item['correct_answer'] ?? null),
                'explanation' => $this->cleanText($item['explanation'] ?? null),
                'source_paragraph' => $this->sanitizeSourceText(
                    $this->cleanText($item['source_paragraph'] ?? null),
                    $questionSet->subject,
                    $questionSet->grade
                ),
                'needs_image' => (bool) ($item['needs_image'] ?? false),
                'image_recommendation' => $this->cleanText($item['image_recommendation'] ?? null),
                'image_description' => $imageDescription,
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
