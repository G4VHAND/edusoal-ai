<?php

namespace App\Policies;

use App\Models\QuestionSet;
use App\Models\User;

/**
 * Policy untuk QuestionSet.
 *
 * Menggantikan pengecekan manual:
 *   if ($questionSet->user_id !== auth()->id()) abort(403);
 *
 * Cara pakai di controller:
 *   $this->authorize('view', $questionSet);
 *   $this->authorize('update', $questionSet);
 *   $this->authorize('delete', $questionSet);
 */
class QuestionSetPolicy
{
    /**
     * User bisa melihat question set miliknya sendiri.
     */
    public function view(User $user, QuestionSet $questionSet): bool
    {
        return $user->id === $questionSet->user_id;
    }

    /**
     * User bisa membuat question set baru (semua user yang login bisa).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * User hanya bisa update question set miliknya sendiri.
     */
    public function update(User $user, QuestionSet $questionSet): bool
    {
        return $user->id === $questionSet->user_id;
    }

    /**
     * User hanya bisa delete question set miliknya sendiri.
     */
    public function delete(User $user, QuestionSet $questionSet): bool
    {
        return $user->id === $questionSet->user_id;
    }

    /**
     * User hanya bisa export question set miliknya sendiri.
     */
    public function export(User $user, QuestionSet $questionSet): bool
    {
        return $user->id === $questionSet->user_id;
    }
}
