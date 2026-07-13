<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Services\AI\QuestionGenerationService;
use Illuminate\Http\Request;

/**
 * Edit manual satu soal (soal, opsi, jawaban, pembahasan) — BUKAN
 * generate ulang lewat AI. Ini untuk kasus hasil AI kurang akurat dan
 * guru mau langsung perbaiki sendiri sebelum export, tanpa perlu
 * generate ulang semuanya (lihat QuestionSetController::edit/update
 * untuk fitur "Generate Ulang" yang benar-benar memanggil AI lagi).
 */
class QuestionController extends Controller
{
    public function edit(Question $question)
    {
        $this->authorize('update', $question);

        $question->load('questionSet');

        return view('questions.edit', compact('question'));
    }

    public function update(Request $request, Question $question, QuestionGenerationService $service)
    {
        $this->authorize('update', $question);

        $questionSet = $question->questionSet;
        $isMultipleChoice = $questionSet->question_type === 'multiple_choice';

        $rules = [
            'question_text' => 'required|string|max:5000',
            'correct_answer' => 'required|string|max:2000',
            'explanation' => 'nullable|string|max:5000',
        ];

        if ($isMultipleChoice) {
            $rules['option_a'] = 'required|string|max:1000';
            $rules['option_b'] = 'required|string|max:1000';
            $rules['option_c'] = 'required|string|max:1000';
            $rules['option_d'] = 'required|string|max:1000';
        }

        $validated = $request->validate($rules, [], [
            'question_text' => 'teks soal',
            'correct_answer' => 'jawaban',
            'explanation' => 'pembahasan',
            'option_a' => 'opsi A',
            'option_b' => 'opsi B',
            'option_c' => 'opsi C',
            'option_d' => 'opsi D',
        ]);

        if ($isMultipleChoice) {
            // Guru mengetik jawaban sebagai huruf (A/B/C/D) di form —
            // pastikan tersimpan konsisten dengan format AI (huruf kapital
            // tunggal), supaya tampilan & export tetap benar.
            $validated['correct_answer'] = strtoupper(trim($validated['correct_answer']));
        }

        // Pakai cleanText() yang sama dipakai hasil AI, supaya konsisten:
        // rapikan whitespace tapi tetap pertahankan **bold** kalau guru
        // sengaja menandai sesuatu secara manual juga.
        foreach ($validated as $key => $value) {
            $validated[$key] = $service->cleanText($value);
        }

        $question->update($validated);

        return redirect()
            ->route('bank-soal.show', $questionSet->id)
            ->with('success', 'Soal berhasil diperbarui.');
    }
}
