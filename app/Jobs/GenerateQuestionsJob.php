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

class GenerateQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        private readonly int   $questionSetId,
        private readonly array $data,
    ) {}

    public function handle(): void
    {
        $questionSet = QuestionSet::findOrFail($this->questionSetId);

        // Skip image upload jika fallback ke Groq (Groq tidak support Vision)
        $hasImage = !empty($this->data['material_image']);

        $payload = [
            'subject'           => $this->data['subject'],
            'grade'             => $this->data['grade'],
            'topic'             => $this->data['topic'],
            'question_type'     => $this->data['question_type'],
            'difficulty'        => $this->data['difficulty'],
            'curriculum'        => $this->data['curriculum']      ?? 'merdeka',
            'assessment_type'   => $this->data['assessment_type'] ?? 'reguler',
            'total_questions'   => $this->data['total_questions'],
            'material_text'     => $this->data['material_text']  ?? null,
            'material_image'    => $this->data['material_image'] ?? null,
            'image_description' => null,
        ];

        // ── Auto-fallback: coba Gemini dulu, jika gagal coba Groq ──────────────
        $providers   = $hasImage ? ['gemini'] : ['gemini', 'groq'];
        $aiResponse  = null;
        $lastError   = null;
        $usedFallback = false;

        foreach ($providers as $index => $providerName) {
            try {
                $service    = AIServiceFactory::make($providerName);
                $aiResponse = $service->generateQuestions($payload);
                $usedFallback = $index > 0;
                break; // berhasil, hentikan loop
            } catch (\Exception $e) {
                $lastError = $e;
                continue; // coba provider berikutnya
            }
        }

        if ($aiResponse === null) {
            // Semua provider gagal
            $questionSet->update([
                'status'          => 'failed',
                'is_ai_generated' => false,
                'ai_error'        => $lastError?->getMessage() ?? 'Semua provider AI gagal merespons.',
            ]);

            throw $lastError ?? new \Exception('Semua provider AI gagal merespons.');
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

            $questionSet->update([
                'status'          => 'completed',
                'is_ai_generated' => true,
                'ai_model'        => $aiResponse['model'] ?? null,
                'ai_prompt'       => $aiResponse['prompt'],
                'ai_result'       => $aiResponse['raw_result'],
                'ai_error'        => $usedFallback ? 'Fallback ke provider cadangan digunakan.' : null,
            ]);

            $questionSet->user?->incrementQuota();

            Cache::forget("dashboard:{$questionSet->user_id}:all");
            Cache::forget("dashboard:{$questionSet->user_id}:7days");
            Cache::forget("dashboard:{$questionSet->user_id}:30days");
            Cache::forget("dashboard:{$questionSet->user_id}:year");

        } catch (\Exception $e) {
            $questionSet->update([
                'status'          => 'failed',
                'is_ai_generated' => false,
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