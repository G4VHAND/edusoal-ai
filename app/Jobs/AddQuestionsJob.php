<?php

namespace App\Jobs;

use App\Models\QuestionSet;
use App\Services\AI\QuestionGenerationService;
use App\Services\Audit\AuditLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        private readonly int $questionSetId,
        private readonly int $additionalCount,
    ) {}

    public function handle(QuestionGenerationService $service): void
    {
        $questionSet = QuestionSet::findOrFail($this->questionSetId);

        // Job berjalan di proses queue worker — TIDAK ada Auth::user(),
        // jadi user_id/school_id harus dikirim manual ke AuditLogService.
        $userId = $questionSet->user_id;
        $schoolId = $questionSet->user?->school_id;

        $payload = [
            'subject' => $questionSet->subject,
            'grade' => $questionSet->grade,
            'topic' => $questionSet->topic,
            'question_type' => $questionSet->question_type,
            'difficulty' => $questionSet->difficulty,
            'curriculum' => $questionSet->curriculum ?? 'merdeka',
            'assessment_type' => $questionSet->assessment_type ?? 'reguler',
            'total_questions' => $this->additionalCount,
            'material_text' => null,
            'material_image' => null,
            'image_description' => null,
        ];

        $existingCount = $questionSet->questions()->count();

        try {
            $result = $service->generateWithFallback($payload, hasImage: false);
        } catch (\Exception $e) {
            // Gagal total — kembalikan total_questions ke jumlah soal yang
            // benar-benar ada, supaya tidak mismatch dengan tampilan.
            $questionSet->update([
                'status' => 'failed',
                'total_questions' => $existingCount,
                'ai_error' => $e->getMessage() ?: 'Semua provider AI gagal merespons saat menambah soal.',
            ]);

            AuditLogService::log(
                module: 'AI',
                event: 'failed',
                description: "Generate soal tambahan gagal untuk bank soal '{$questionSet->title}'",
                properties: [
                    'question_set_id' => $questionSet->id,
                    'additional_count' => $this->additionalCount,
                    'error' => $e->getMessage(),
                ],
                userId: $userId,
                schoolId: $schoolId,
            );

            throw $e;
        }

        $aiResponse = $result['response'];

        try {
            $decoded = $service->parseAiJson($aiResponse['raw_result']);

            $service->createQuestions(
                $questionSet,
                $decoded['questions'],
                $aiResponse['image_description'] ?? null,
            );

            $finalCount = $questionSet->questions()->count();

            // Hanya isi kalau belum ada — soal tambahan biasanya untuk topik
            // yang sama, jangan sampai menimpa referensi awal yang sudah benar.
            $newSourceReference = $questionSet->source_reference
                ?: $service->sanitizeSourceText(
                    $service->cleanText($decoded['source_reference'] ?? null),
                    $questionSet->subject,
                    $questionSet->grade
                );

            $questionSet->update([
                'status' => 'completed',
                'total_questions' => $finalCount,
                'source_reference' => $newSourceReference,
                'ai_error' => null,
            ]);

            $questionSet->user?->incrementQuota();
            $service->clearDashboardCache($questionSet->user_id);

            AuditLogService::log(
                module: 'AI',
                event: 'finish',
                description: "AI berhasil menambahkan {$this->additionalCount} soal ke bank soal '{$questionSet->title}'",
                properties: [
                    'question_set_id' => $questionSet->id,
                    'additional_count' => $this->additionalCount,
                    'total_questions' => $finalCount,
                ],
                userId: $userId,
                schoolId: $schoolId,
            );

        } catch (\Exception $e) {
            // Soal lama tetap aman, hanya gagal menambah yang baru.
            $questionSet->update([
                'status' => 'failed',
                'total_questions' => $existingCount,
                'ai_error' => $e->getMessage(),
            ]);

            AuditLogService::log(
                module: 'AI',
                event: 'failed',
                description: "Generate soal tambahan gagal untuk bank soal '{$questionSet->title}'",
                properties: [
                    'question_set_id' => $questionSet->id,
                    'additional_count' => $this->additionalCount,
                    'error' => $e->getMessage(),
                ],
                userId: $userId,
                schoolId: $schoolId,
            );

            throw $e;
        }
    }
}
