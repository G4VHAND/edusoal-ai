<?php

namespace App\Jobs;

use App\Models\Question;
use App\Models\QuestionSet;
use App\Services\AI\AIServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Generate soal TAMBAHAN untuk question set yang sudah ada, dipicu saat
 * user menaikkan total_questions lewat form edit (mis. 5 -> 8, tinggal
 * generate 3 soal baru dan ditambahkan ke soal yang sudah ada).
 *
 * Berbeda dengan GenerateQuestionsJob yang membuat set soal dari nol,
 * job ini APPEND ke soal yang sudah ada, tidak menghapus/menimpa apa pun.
 */
class AddQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        private readonly int $questionSetId,
        private readonly int $additionalCount,
    ) {}

    public function handle(): void
    {
        $questionSet = QuestionSet::findOrFail($this->questionSetId);

        $payload = [
            'subject'           => $questionSet->subject,
            'grade'             => $questionSet->grade,
            'topic'             => $questionSet->topic,
            'question_type'     => $questionSet->question_type,
            'difficulty'        => $questionSet->difficulty,
            'curriculum'        => $questionSet->curriculum      ?? 'merdeka',
            'assessment_type'   => $questionSet->assessment_type ?? 'reguler',
            'total_questions'   => $this->additionalCount,
            'material_text'     => null,
            'material_image'    => null,
            'image_description' => null,
        ];

        // ── Auto-fallback: coba Gemini dulu, jika gagal coba Groq ──────────────
        $aiResponse = null;
        $lastError  = null;

        foreach (['gemini', 'groq'] as $providerName) {
            try {
                $service    = AIServiceFactory::make($providerName);
                $aiResponse = $service->generateQuestions($payload);
                break;
            } catch (\Exception $e) {
                $lastError = $e;
                continue;
            }
        }

        $existingCount = $questionSet->questions()->count();

        if ($aiResponse === null) {
            // Gagal total — kembalikan total_questions ke jumlah soal yang
            // benar-benar ada, supaya tidak mismatch dengan tampilan.
            $questionSet->update([
                'status'          => 'failed',
                'total_questions' => $existingCount,
                'ai_error'        => $lastError?->getMessage() ?? 'Semua provider AI gagal merespons saat menambah soal.',
            ]);

            throw $lastError ?? new \Exception('Semua provider AI gagal merespons saat menambah soal.');
        }

        try {
            $decoded = $this->parseAiJson($aiResponse['raw_result']);

            foreach ($decoded['questions'] as $item) {
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
                    'image_description'    => $aiResponse['image_description'] ?? null,
                ]);
            }

            $finalCount = $questionSet->questions()->count();

            $questionSet->update([
                'status'          => 'completed',
                'total_questions' => $finalCount,
                'ai_error'        => null,
            ]);

            $questionSet->user?->incrementQuota();

            Cache::forget("dashboard:{$questionSet->user_id}:all");
            Cache::forget("dashboard:{$questionSet->user_id}:7days");
            Cache::forget("dashboard:{$questionSet->user_id}:30days");
            Cache::forget("dashboard:{$questionSet->user_id}:year");

        } catch (\Exception $e) {
            // Soal lama tetap aman, hanya gagal menambah yang baru.
            $questionSet->update([
                'status'          => 'failed',
                'total_questions' => $existingCount,
                'ai_error'        => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function parseAiJson(string $raw): array
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

    private function cleanText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return trim(str_replace(['**', '*', '```'], '', $text));
    }
}
