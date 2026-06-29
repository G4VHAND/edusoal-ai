<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateQuestionsJob;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Services\AI\AIServiceFactory;
use App\Services\Material\MaterialReaderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuestionSetController extends Controller
{
    public function create()
    {
        return view('question_sets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'subject'         => 'required|string|max:255',
            'grade'           => 'required|string|in:Kelas 1 SD,Kelas 2 SD,Kelas 3 SD,Kelas 4 SD,Kelas 5 SD,Kelas 6 SD,Kelas 7 SMP,Kelas 8 SMP,Kelas 9 SMP,Kelas 10 SMA,Kelas 11 SMA,Kelas 12 SMA,Kelas 10 SMK,Kelas 11 SMK,Kelas 12 SMK',
            'topic'           => 'required|string|max:255',
            'question_type'   => 'required|string|in:multiple_choice,essay',
            'difficulty'      => 'required|string|in:mudah,sedang,sulit',
            'total_questions' => 'required|integer|min:1|max:50',
            'ai_provider'     => 'required|string|in:' . implode(',', AIServiceFactory::supported()),
            'material_file'   => [
                'nullable', 'file', 'max:5120',
                'mimetypes:application/pdf,application/msword,'
                    . 'application/vnd.openxmlformats-officedocument.wordprocessingml.document,'
                    . 'text/plain',
            ],
            'material_image'  => [
                'nullable', 'file', 'max:5120',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp',
            ],
        ]);

        $materialPath = $materialOriginalName = $materialText = $materialImage = null;

        if ($request->hasFile('material_file')) {
            $file                 = $request->file('material_file');
            $materialOriginalName = $file->getClientOriginalName();
            $materialPath         = $file->store('materials', 'local');
            $materialText         = (new MaterialReaderService())->extractText($materialPath);
        }

        if ($request->hasFile('material_image')) {
            $materialImage = $request->file('material_image')->store('material-images', 'local');
        }

        $questionSet = QuestionSet::create([
            'user_id'                => auth()->id(),
            'title'                  => $validated['title'],
            'subject'                => $validated['subject'],
            'grade'                  => $validated['grade'],
            'topic'                  => $validated['topic'],
            'question_type'          => $validated['question_type'],
            'difficulty'             => $validated['difficulty'],
            'total_questions'        => $validated['total_questions'],
            'ai_provider'            => $validated['ai_provider'],
            'status'                 => 'pending',
            'is_ai_generated'        => false,
            'ai_model'               => null,
            'ai_prompt'              => null,
            'ai_result'              => null,
            'ai_error'               => null,
            'material_file'          => $materialPath,
            'material_original_name' => $materialOriginalName,
            'material_image'         => $materialImage,
        ]);

        GenerateQuestionsJob::dispatch($questionSet->id, [
            'subject'         => $validated['subject'],
            'grade'           => $validated['grade'],
            'topic'           => $validated['topic'],
            'question_type'   => $validated['question_type'],
            'difficulty'      => $validated['difficulty'],
            'total_questions' => $validated['total_questions'],
            'ai_provider'     => $validated['ai_provider'],
            'material_text'   => $materialText,
            'material_image'  => $materialImage,
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

    public function update(Request $request, QuestionSet $questionSet)
    {
        $this->authorize('update', $questionSet);

        $request->validate([
            'title'           => 'required|string|max:255',
            'subject'         => 'required|string|max:255',
            'grade'           => 'required|string|in:Kelas 1 SD,Kelas 2 SD,Kelas 3 SD,Kelas 4 SD,Kelas 5 SD,Kelas 6 SD,Kelas 7 SMP,Kelas 8 SMP,Kelas 9 SMP,Kelas 10 SMA,Kelas 11 SMA,Kelas 12 SMA,Kelas 10 SMK,Kelas 11 SMK,Kelas 12 SMK',
            'topic'           => 'required|string|max:255',
            'question_type'   => 'required|string|in:multiple_choice,essay',
            'difficulty'      => 'required|string|in:mudah,sedang,sulit',
            'total_questions' => 'required|integer|min:1|max:50',
        ]);

        $questionSet->update($request->only([
            'title', 'subject', 'grade', 'topic',
            'question_type', 'difficulty', 'total_questions',
        ]));

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

        $questionSet->delete();

        return redirect()
            ->route('bank-soal')
            ->with('success', 'Bank soal berhasil dihapus.');
    }

    public function status(QuestionSet $questionSet)
    {
        $this->authorize('view', $questionSet);

        return response()->json([
            'status'        => $questionSet->status,
            'has_questions' => $questionSet->questions()->count() > 0,
            'ai_error'      => $questionSet->ai_error,
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

        return back()->with('success', 'Gambar berhasil diupload.');
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