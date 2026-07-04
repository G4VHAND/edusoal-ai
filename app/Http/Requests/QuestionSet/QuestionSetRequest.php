<?php

namespace App\Http\Requests\QuestionSet;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Rules dasar yang sama persis dipakai oleh Store & Update QuestionSet —
 * diekstrak ke sini supaya tidak duplikasi (dulu ditulis dua kali di
 * QuestionSetController::store() dan ::update()).
 */
abstract class QuestionSetRequest extends FormRequest
{
    protected function sharedRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'grade' => 'required|string|in:Kelas 1 SD,Kelas 2 SD,Kelas 3 SD,Kelas 4 SD,Kelas 5 SD,Kelas 6 SD,Kelas 7 SMP,Kelas 8 SMP,Kelas 9 SMP,Kelas 10 SMA,Kelas 11 SMA,Kelas 12 SMA,Kelas 10 SMK,Kelas 11 SMK,Kelas 12 SMK',
            'topic' => 'required|string|max:255',
            'question_type' => 'required|string|in:multiple_choice,essay',
            'difficulty' => 'required|string|in:mudah,sedang,sulit',
            'curriculum' => 'required|string|in:merdeka,k13',
            'assessment_type' => 'required|string|in:reguler,hots,akm',
        ];
    }
}
