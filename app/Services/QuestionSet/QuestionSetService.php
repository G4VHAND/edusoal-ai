<?php

namespace App\Services\QuestionSet;

use App\Jobs\AddQuestionsJob;
use App\Jobs\GenerateQuestionsJob;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Storage\QuestionSetStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Business logic untuk QuestionSet: create, update, delete, dan operasi
 * per-soal (upload/hapus gambar, hapus satu soal).
 *
 * Controller HANYA boleh: validasi request (lewat FormRequest), panggil
 * method di sini, lalu redirect/response. Semua keputusan "boleh/tidak",
 * "apa yang disimpan", dan "apa yang di-log" ada di service ini.
 *
 * Aturan bisnis yang melanggar constraint (quota habis, hapus soal
 * terakhir) dilempar sebagai ValidationException — Laravel otomatis
 * mengubahnya jadi redirect back() + withErrors(), sama persis seperti
 * behavior lama, tanpa controller perlu try/catch manual.
 */
class QuestionSetService
{
    public function __construct(
        private readonly QuestionSetStorageService $storage,
    ) {}

    /**
     * Buat QuestionSet baru + dispatch job generate AI.
     *
     * @param  array  $validated  Hasil StoreQuestionSetRequest::validated()
     */
    public function create(
        array $validated,
        User $user,
        ?UploadedFile $materialFile,
        ?UploadedFile $materialImage,
    ): QuestionSet {
        // Jangan percaya begitu saja input 'ai_provider' dari form — guru
        // tidak boleh memilih sendiri (provider ditentukan admin sekolah),
        // dan individual hanya boleh memilih kalau plan-nya mengizinkan.
        $provider = $user->resolveAiProvider($validated['ai_provider'] ?? null);

        $material = $materialFile ? $this->storage->storeMaterialFile($materialFile) : null;
        $materialImagePath = $materialImage ? $this->storage->storeMaterialImage($materialImage) : null;

        $questionSet = QuestionSet::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'subject' => $validated['subject'],
            'grade' => $validated['grade'],
            'topic' => $validated['topic'],
            'question_type' => $validated['question_type'],
            'difficulty' => $validated['difficulty'],
            'curriculum' => $validated['curriculum'],
            'assessment_type' => $validated['assessment_type'],
            'total_questions' => $validated['total_questions'],
            'ai_provider' => $provider,
            'status' => 'pending',
            'is_ai_generated' => false,
            'ai_model' => null,
            'ai_prompt' => null,
            'ai_result' => null,
            'ai_error' => null,
            'material_file' => $material['path'] ?? null,
            'material_original_name' => $material['original_name'] ?? null,
            'material_image' => $materialImagePath,
        ]);

        AuditLogService::log(
            module: 'Bank Soal',
            event: 'create',
            description: "Membuat bank soal '{$questionSet->title}'",
            properties: [
                'question_set_id' => $questionSet->id,
                'subject' => $questionSet->subject,
                'grade' => $questionSet->grade,
            ]
        );

        // Dicatat terpisah dari log "create" di atas — supaya kalau AI-nya
        // gagal (lihat GenerateQuestionsJob), riwayat ini tetap menunjukkan
        // generate memang benar-benar sempat mulai jalan dengan parameter apa.
        AuditLogService::log(
            module: 'AI',
            event: 'generate',
            description: 'Generate soal menggunakan '.ucfirst($provider),
            properties: [
                'question_set_id' => $questionSet->id,
                'provider' => $provider,
                'total_questions' => $validated['total_questions'],
                'curriculum' => $validated['curriculum'],
                'assessment_type' => $validated['assessment_type'],
            ]
        );

        GenerateQuestionsJob::dispatch($questionSet->id, [
            'subject' => $validated['subject'],
            'grade' => $validated['grade'],
            'topic' => $validated['topic'],
            'question_type' => $validated['question_type'],
            'difficulty' => $validated['difficulty'],
            'curriculum' => $validated['curriculum'],
            'assessment_type' => $validated['assessment_type'],
            'total_questions' => $validated['total_questions'],
            'ai_provider' => $provider,
            'material_text' => $material['text'] ?? null,
            'material_image' => $materialImagePath,
        ]);

        return $questionSet;
    }

    /**
     * Update metadata QuestionSet. Kalau total_questions dinaikkan,
     * dispatch job untuk generate soal tambahan (append, bukan replace).
     *
     * @param  array  $validated  Hasil UpdateQuestionSetRequest::validated()
     * @param  int  $currentCount  Jumlah soal aktual saat ini (dari request->currentQuestionCount())
     * @return array{question_set: QuestionSet, additional: int} Controller pakai 'additional'
     *         untuk menentukan pesan flash yang tepat.
     *
     * @throws ValidationException  Kalau quota user sudah habis untuk menambah soal.
     */
    public function update(QuestionSet $questionSet, array $validated, int $currentCount): array
    {
        $newTotal = (int) $validated['total_questions'];
        $additional = $newTotal - $currentCount;
        $user = $questionSet->user;

        if ($additional > 0 && ! $user->hasQuota()) {
            $remaining = $user->remainingQuota();

            throw ValidationException::withMessages([
                'quota' => "Quota generate soal bulan ini sudah habis (sisa: {$remaining}). "
                    .'Upgrade plan untuk mendapatkan lebih banyak quota, atau turunkan jumlah soal.',
            ]);
        }

        // Field yang relevan buat riwayat — sengaja tidak termasuk kolom AI
        // internal (ai_prompt, ai_result, dll) karena itu bukan sesuatu yang
        // "diubah" user secara sadar lewat form ini.
        $trackedFields = [
            'title', 'subject', 'grade', 'topic', 'question_type',
            'difficulty', 'curriculum', 'assessment_type', 'total_questions',
        ];
        $before = $questionSet->only($trackedFields);

        $questionSet->update([
            'title' => $validated['title'],
            'subject' => $validated['subject'],
            'grade' => $validated['grade'],
            'topic' => $validated['topic'],
            'question_type' => $validated['question_type'],
            'difficulty' => $validated['difficulty'],
            'curriculum' => $validated['curriculum'],
            'assessment_type' => $validated['assessment_type'],
            // Selama masih menunggu soal tambahan, total_questions sementara
            // tetap ikut angka lama; job yang akan mengisi angka final agar
            // tidak mismatch dengan jumlah soal aktual jika generate gagal.
            'total_questions' => $additional > 0 ? $currentCount : $newTotal,
            'status' => $additional > 0 ? 'processing' : $questionSet->status,
        ]);

        $changes = AuditLogService::diff($before, $questionSet->only($trackedFields));

        AuditLogService::log(
            module: 'Bank Soal',
            event: 'update',
            description: $changes
                ? "Mengubah bank soal '{$questionSet->title}': ".implode(', ', array_keys($changes))
                : "Mengubah bank soal '{$questionSet->title}' (tidak ada perubahan data)",
            properties: [
                'question_set_id' => $questionSet->id,
                'changes' => $changes,
            ]
        );

        if ($additional > 0) {
            AddQuestionsJob::dispatch($questionSet->id, $additional);
        }

        return ['question_set' => $questionSet, 'additional' => $additional];
    }

    public function delete(QuestionSet $questionSet): void
    {
        $this->storage->deleteMaterialFile($questionSet->material_file);

        // Snapshot sebelum dihapus — setelah delete(), data ini sudah
        // tidak bisa dilihat lagi di tabel question_sets (kecuali soft
        // delete belum di-purge), jadi audit log jadi satu-satunya jejak.
        $snapshot = [
            'question_set_id' => $questionSet->id,
            'title' => $questionSet->title,
            'subject' => $questionSet->subject,
            'grade' => $questionSet->grade,
            'total_questions' => $questionSet->total_questions,
        ];

        $questionSet->delete();

        AuditLogService::log(
            module: 'Bank Soal',
            event: 'delete',
            description: "Menghapus bank soal '{$snapshot['title']}'",
            properties: $snapshot
        );
    }

    public function status(QuestionSet $questionSet): array
    {
        return [
            'status' => $questionSet->status,
            'has_questions' => $questionSet->questions()->count() > 0,
            'ai_error' => $questionSet->ai_error,
        ];
    }

    public function uploadQuestionImage(QuestionSet $questionSet, Question $question, UploadedFile $image): void
    {
        $this->assertQuestionBelongsToSet($questionSet, $question);

        $question->update([
            'image_path' => $this->storage->storeQuestionImage($image, $question->image_path),
        ]);

        $questionNumber = $this->questionNumber($questionSet, $question);

        AuditLogService::log(
            module: 'Soal',
            event: 'upload_image',
            description: "Mengunggah gambar pada soal nomor {$questionNumber}",
            properties: [
                'question_id' => $question->id,
                'question_set_id' => $questionSet->id,
                'question_number' => $questionNumber,
            ]
        );
    }

    public function deleteQuestionImage(QuestionSet $questionSet, Question $question): void
    {
        $this->assertQuestionBelongsToSet($questionSet, $question);

        if (! $question->image_path) {
            return;
        }

        $this->storage->deleteQuestionImage($question->image_path);
        $questionNumber = $this->questionNumber($questionSet, $question);

        $question->update(['image_path' => null]);

        AuditLogService::log(
            module: 'Soal',
            event: 'delete_image',
            description: "Menghapus gambar soal nomor {$questionNumber}",
            properties: [
                'question_id' => $question->id,
                'question_set_id' => $questionSet->id,
                'question_number' => $questionNumber,
            ]
        );
    }

    /**
     * Hapus satu soal saja dari bank soal (bukan hapus seluruh set).
     * total_questions otomatis disesuaikan supaya tetap sinkron dengan
     * jumlah soal aktual yang tersisa.
     *
     * @throws ValidationException  Kalau ini soal terakhir di bank soal.
     */
    public function destroyQuestion(QuestionSet $questionSet, Question $question): void
    {
        $this->assertQuestionBelongsToSet($questionSet, $question);

        if ($questionSet->questions()->count() <= 1) {
            throw ValidationException::withMessages([
                'question' => 'Tidak bisa menghapus soal terakhir. Hapus seluruh bank soal jika ingin mengosongkannya.',
            ]);
        }

        if ($question->image_path) {
            $this->storage->deleteQuestionImage($question->image_path);
        }

        // Hitung nomor urutnya SEBELUM dihapus — setelah delete() soal ini
        // tidak lagi terhitung di antara sibling-nya.
        $questionNumber = $this->questionNumber($questionSet, $question);

        $question->delete();

        AuditLogService::log(
            module: 'Soal',
            event: 'delete',
            description: "Menghapus soal nomor {$questionNumber} dari bank soal '{$questionSet->title}'",
            properties: [
                'question_id' => $question->id,
                'question_set_id' => $questionSet->id,
                'question_number' => $questionNumber,
            ]
        );

        $questionSet->update([
            'total_questions' => $questionSet->questions()->count(),
        ]);
    }

    /**
     * Path lengkap di disk untuk gambar soal, siap dikirim lewat response()->file().
     * Melempar 404 kalau soal tidak punya gambar atau filenya hilang di server.
     */
    public function questionImagePath(QuestionSet $questionSet, Question $question): string
    {
        $this->assertQuestionBelongsToSet($questionSet, $question);

        if (! $question->image_path || ! $this->storage->questionImageExists($question->image_path)) {
            abort(404);
        }

        return $this->storage->questionImageFullPath($question->image_path);
    }

    /**
     * Pastikan $question memang anak dari $questionSet — mencegah orang
     * mengakses/mengubah soal milik bank soal lain lewat URL manipulasi
     * (mis. /bank-soal/1/questions/999 padahal soal #999 milik bank soal lain).
     */
    private function assertQuestionBelongsToSet(QuestionSet $questionSet, Question $question): void
    {
        if ($question->question_set_id !== $questionSet->id) {
            abort(403);
        }
    }

    /**
     * Nomor urut soal dalam bank soal ini — sama seperti nomor yang
     * dilihat guru di halaman show (urutan berdasar id ascending).
     */
    private function questionNumber(QuestionSet $questionSet, Question $question): int
    {
        return $questionSet->questions()->where('id', '<=', $question->id)->count();
    }
}
