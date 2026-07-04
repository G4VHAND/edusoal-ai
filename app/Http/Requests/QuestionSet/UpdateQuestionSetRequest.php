<?php

namespace App\Http\Requests\QuestionSet;

class UpdateQuestionSetRequest extends QuestionSetRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('questionSet'));
    }

    /**
     * Jumlah soal AKTUAL yang sudah ada di question set ini (bukan
     * kolom total_questions, tapi hitungan baris Question sungguhan).
     */
    public function currentQuestionCount(): int
    {
        return $this->route('questionSet')->questions()->count();
    }

    public function rules(): array
    {
        $currentCount = $this->currentQuestionCount();

        return $this->sharedRules() + [
            'total_questions' => [
                'required', 'integer', 'min:1', 'max:50',
                function ($attribute, $value, $fail) use ($currentCount) {
                    if ($value < $currentCount) {
                        $fail("Tidak bisa mengurangi jumlah soal di sini (saat ini ada {$currentCount} soal). Hapus soal secara manual dari halaman detail jika ingin menguranginya.");
                    }
                },
            ],
        ];
    }
}
