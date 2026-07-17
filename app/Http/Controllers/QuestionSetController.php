<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionSet\StoreQuestionSetRequest;
use App\Http\Requests\QuestionSet\UpdateQuestionSetRequest;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Services\QuestionSet\QuestionSetService;
use Illuminate\Http\Request;

/**
 * Controller ini sengaja tipis — semua business logic (validasi bisnis,
 * penyimpanan file, dispatch job, audit log) ada di QuestionSetService.
 * Tanggung jawab controller cuma: terima request tervalidasi, panggil
 * service, lalu redirect/response.
 */
class QuestionSetController extends Controller
{
    public function __construct(
        private readonly QuestionSetService $service,
    ) {}

    public function create()
    {
        return view('question_sets.create');
    }

    public function store(StoreQuestionSetRequest $request)
    {
        $questionSet = $this->service->create(
            $request->validated(),
            $request->user(),
            $request->file('material_file'),
            $request->file('material_image'),
        );

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
        // Quota habis dilempar sebagai ValidationException oleh service —
        // Laravel otomatis mengubahnya jadi back()->withInput()->withErrors().
        $result = $this->service->update(
            $questionSet,
            $request->validated(),
            $request->currentQuestionCount(),
        );

        if ($result['additional'] > 0) {
            return redirect()
                ->route('bank-soal.show', $questionSet->id)
                ->with('info', "Menambahkan {$result['additional']} soal baru. Halaman akan otomatis diperbarui saat selesai.");
        }

        return redirect()
            ->route('bank-soal.show', $questionSet->id)
            ->with('success', 'Bank soal berhasil diperbarui.');
    }

    public function destroy(QuestionSet $questionSet)
    {
        $this->authorize('delete', $questionSet);
        $this->service->delete($questionSet);

        return redirect()
            ->route('bank-soal')
            ->with('success', 'Bank soal berhasil dihapus.');
    }

    public function status(QuestionSet $questionSet)
    {
        $this->authorize('view', $questionSet);

        return response()->json($this->service->status($questionSet));
    }

    public function uploadQuestionImage(Request $request, QuestionSet $questionSet, Question $question)
    {
        $this->authorize('update', $questionSet);

        $request->validate([
            'image' => ['required', 'file', 'max:5120', 'mimetypes:image/jpeg,image/png,image/gif,image/webp'],
        ]);

        $this->service->uploadQuestionImage($questionSet, $question, $request->file('image'));

        return back()->with('success', 'Gambar berhasil diupload.');
    }

    public function destroyQuestion(QuestionSet $questionSet, Question $question)
    {
        $this->authorize('update', $questionSet);
        $this->service->destroyQuestion($questionSet, $question);

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    public function deleteQuestionImage(QuestionSet $questionSet, Question $question)
    {
        $this->authorize('update', $questionSet);
        $this->service->deleteQuestionImage($questionSet, $question);

        return back()->with('success', 'Gambar berhasil dihapus.');
    }

    public function serveQuestionImage(QuestionSet $questionSet, Question $question)
    {
        $this->authorize('view', $questionSet);

        return response()->file($this->service->questionImagePath($questionSet, $question));
    }
}