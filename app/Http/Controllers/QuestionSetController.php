<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionSet\StoreQuestionSetRequest;
use App\Http\Requests\QuestionSet\UpdateQuestionSetRequest;
use App\Jobs\AddQuestionsJob;
use App\Jobs\GenerateQuestionsJob;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Services\Audit\AuditLogService;
use App\Services\Material\MaterialReaderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuestionSetController extends Controller
{
    public function create()
    {
        return view('question_sets.create');
    }

    public function store(StoreQuestionSetRequest $request)
    {
        $validated = $request->validated();

        // Jangan percaya begitu saja input 'ai_provider' dari form — guru
        // tidak boleh memilih sendiri (provider ditentukan admin sekolah),
        // dan individual hanya boleh memilih kalau plan-nya mengizinkan.
        $provider = auth()->user()->resolveAiProvider($validated['ai_provider'] ?? null);

        $materialPath = $materialOriginalName = $materialText = $materialImage = null;

        if ($request->hasFile('material_file')) {
            $file = $request->file('material_file');
            $materialOriginalName = $file->getClientOriginalName();
            $materialPath = $file->store('materials', 'local');
            $materialText = (new MaterialReaderService)->extractText($materialPath);
        }

        if ($request->hasFile('material_image')) {
            $materialImage = $request->file('material_image')->store('material-images', 'local');
        }

        $questionSet = QuestionSet::create([
            'user_id' => auth()->id(),
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
            'material_file' => $materialPath,
            'material_original_name' => $materialOriginalName,
            'material_image' => $materialImage,
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
            'material_text' => $materialText,
            'material_image' => $materialImage,
        ]);

        return redirect()
            ->route('bank-soal.show', $questionSet->id)
            ->with('info', 'Bank soal sedang diproses oleh AI. Halaman akan otomatis diperbarui.');
    }

    public function show(QuestionSet $questionSet)
    {
        $this->authorize('view', $questionSet);
        $questionSet->load('questions');

        return view('question_sets.show', compact('questionSet'));
    }

    public function edit(QuestionSet $questionSet)
    {
        $this->authorize('update', $questionSet);

        return view('question_sets.edit', compact('questionSet'));
    }

    public function update(UpdateQuestionSetRequest $request, QuestionSet $questionSet)
    {
        $validated = $request->validated();
        $currentCount = $request->currentQuestionCount();
        $newTotal = (int) $validated['total_questions'];
        $additional = $newTotal - $currentCount;
        $user = $questionSet->user;

        if ($additional > 0 && ! $user->hasQuota()) {
            $remaining = $user->remainingQuota();

            return back()
                ->withInput()
                ->withErrors([
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

            return redirect()
                ->route('bank-soal.show', $questionSet->id)
                ->with('info', "Menambahkan {$additional} soal baru. Halaman akan otomatis diperbarui saat selesai.");
        }

        return redirect()
            ->route('bank-soal.show', $questionSet->id)
            ->with('success', 'Bank soal berhasil diperbarui.');
    }

    public function destroy(QuestionSet $questionSet)
    {
        $this->authorize('delete', $questionSet);

        if ($questionSet->material_file) {
            Storage::disk('local')->delete($questionSet->material_file);
        }

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

        return redirect()
            ->route('bank-soal')
            ->with('success', 'Bank soal berhasil dihapus.');
    }

    public function status(QuestionSet $questionSet)
    {
        $this->authorize('view', $questionSet);

        return response()->json([
            'status' => $questionSet->status,
            'has_questions' => $questionSet->questions()->count() > 0,
            'ai_error' => $questionSet->ai_error,
        ]);
    }

    public function uploadQuestionImage(Request $request, QuestionSet $questionSet, Question $question)
    {
        $this->authorize('update', $questionSet);

        if ($question->question_set_id !== $questionSet->id) {
            abort(403);
        }

        $request->validate([
            'image' => ['required', 'file', 'max:5120', 'mimetypes:image/jpeg,image/png,image/gif,image/webp'],
        ]);

        if ($question->image_path) {
            Storage::disk('local')->delete($question->image_path);
        }

        $question->update([
            'image_path' => $request->file('image')->store('question-images', 'local'),
        ]);

        // Nomor urut soal dalam bank soal ini — sama seperti nomor yang
        // dilihat guru di halaman show (urutan berdasar id ascending).
        $questionNumber = $questionSet->questions()->where('id', '<=', $question->id)->count();

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

        return back()->with('success', 'Gambar berhasil diupload.');
    }

    /**
     * Hapus satu soal saja dari bank soal (bukan hapus seluruh set).
     * total_questions otomatis disesuaikan supaya tetap sinkron dengan
     * jumlah soal aktual yang tersisa.
     */
    public function destroyQuestion(QuestionSet $questionSet, Question $question)
    {
        $this->authorize('update', $questionSet);

        if ($question->question_set_id !== $questionSet->id) {
            abort(403);
        }

        if ($questionSet->questions()->count() <= 1) {
            return back()->withErrors([
                'question' => 'Tidak bisa menghapus soal terakhir. Hapus seluruh bank soal jika ingin mengosongkannya.',
            ]);
        }

        if ($question->image_path) {
            Storage::disk('local')->delete($question->image_path);
        }

        // Hitung nomor urutnya SEBELUM dihapus — setelah delete() soal ini
        // tidak lagi terhitung di antara sibling-nya.
        $questionNumber = $questionSet->questions()->where('id', '<=', $question->id)->count();

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

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    public function deleteQuestionImage(QuestionSet $questionSet, Question $question)
    {
        $this->authorize('update', $questionSet);

        if ($question->question_set_id !== $questionSet->id) {
            abort(403);
        }

        if ($question->image_path) {
            Storage::disk('local')->delete($question->image_path);
            $question->update(['image_path' => null]);

            $questionNumber = $questionSet->questions()->where('id', '<=', $question->id)->count();

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

        return back()->with('success', 'Gambar berhasil dihapus.');
    }

    public function serveQuestionImage(QuestionSet $questionSet, Question $question)
    {
        $this->authorize('view', $questionSet);

        if ($question->question_set_id !== $questionSet->id || ! $question->image_path) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($question->image_path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('local')->path($question->image_path)
        );
    }
}
