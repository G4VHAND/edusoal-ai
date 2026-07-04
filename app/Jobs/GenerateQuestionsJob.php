<?php

namespace App\Jobs;

use App\Models\QuestionSet;
use App\Services\AI\QuestionGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        private readonly int   $questionSetId,
        private readonly array $data,
    ) {}

    public function handle(QuestionGenerationService $service): void
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

        try {
            $result = $service->generateWithFallback($payload, $hasImage);
        } catch (\Exception $e) {
            // Semua provider gagal
            $questionSet->update([
                'status'          => 'failed',
                'is_ai_generated' => false,
                'ai_error'        => $e->getMessage() ?: 'Semua provider AI gagal merespons.',
            ]);

            throw $e;
        }

        $aiResponse   = $result['response'];
        $usedFallback = $result['used_fallback'];

        try {
            $decoded = $service->parseAiJson($aiResponse['raw_result']);

            $service->createQuestions(
                $questionSet,
                $decoded['questions'],
                $aiResponse['image_description'] ?? null,
            );

            $questionSet->update([
                'status'          => 'completed',
                'is_ai_generated' => true,
                'ai_model'        => $aiResponse['model'] ?? null,
                'ai_prompt'       => $aiResponse['prompt'],
                'ai_result'       => $aiResponse['raw_result'],
                'ai_error'        => $usedFallback ? 'Fallback ke provider cadangan digunakan.' : null,
            ]);

            $questionSet->user?->incrementQuota();
            $service->clearDashboardCache($questionSet->user_id);

        } catch (\Exception $e) {
            $questionSet->update([
                'status'          => 'failed',
                'is_ai_generated' => false,
                'ai_error'        => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
