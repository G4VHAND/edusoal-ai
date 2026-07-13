<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

/**
 * Policy untuk edit manual satu soal (bukan question set-nya).
 *
 * Cuma pemilik question set yang boleh mengedit isi soal (soal, opsi,
 * jawaban, pembahasan) secara langsung — supaya guru bisa memperbaiki
 * hasil AI yang kurang akurat sebelum export, tanpa perlu generate ulang.
 */
class QuestionPolicy
{
    public function update(User $user, Question $question): bool
    {
        return $user->id === $question->questionSet->user_id;
    }
}
