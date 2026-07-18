<?php

namespace App\Services\Document;

use App\Models\DocumentTemplate;
use App\Models\QuestionSet;
use App\Models\User;

/**
 * Menentukan DocumentTemplate mana yang dipakai saat export Word.
 *
 * Prioritas pencarian (kalau tidak ada template_id eksplisit):
 *   1. Template default milik sekolah GURU YANG BUAT SOAL (bukan sekolah
 *      yang sedang login — beda kalau super_admin export soal guru lain).
 *   2. Fallback: template default milik sekolah user yang sedang login.
 *   3. Fallback: template personal milik user yang sedang login.
 *
 * Kalau template_id eksplisit dikirim, validasi kepemilikan dulu — pola
 * yang sama seperti di DocumentTemplateController::destroy()/setDefault():
 * admin sekolah hanya boleh pakai template sekolahnya sendiri, guru hanya
 * boleh pakai template miliknya sendiri (atau template default sekolahnya).
 */
class DocumentTemplateResolver
{
    public function resolve(
        QuestionSet $questionSet,
        User $currentUser,
        string $type,
        ?int $templateId,
    ): ?DocumentTemplate {
        if ($templateId) {
            return $this->resolveExplicit($questionSet, $currentUser, $templateId);
        }

        return $this->resolveDefault($questionSet, $currentUser, $type);
    }

    private function resolveExplicit(QuestionSet $questionSet, User $currentUser, int $templateId): ?DocumentTemplate
    {
        $template = DocumentTemplate::find($templateId);

        if (! $template) {
            return null;
        }

        $schoolId = $questionSet->user?->school_id;

        // Basis ownership sama dengan DocumentTemplateService (lihat
        // DocumentTemplate::isOwnedBy()), ditambah 1 pengecualian khusus
        // export: guru (bukan school_admin, tidak "memiliki" template)
        // tetap boleh pakai template default SEKOLAHNYA sendiri saat export,
        // walau dia bukan yang meng-upload template itu.
        $owns = $template->isOwnedBy($currentUser)
            || (! $currentUser->isSchoolAdmin() && $schoolId && $template->school_id === $schoolId);

        abort_unless($owns, 403, 'Anda tidak memiliki akses ke template ini.');

        return $template;
    }

    private function resolveDefault(QuestionSet $questionSet, User $currentUser, string $type): ?DocumentTemplate
    {
        $schoolId = $questionSet->user?->school_id;

        if ($schoolId) {
            $template = DocumentTemplate::where('school_id', $schoolId)
                ->where('type', $type)
                ->where('is_default', true)
                ->first();

            if ($template) {
                return $template;
            }
        }

        if ($currentUser->school_id) {
            $template = DocumentTemplate::where('school_id', $currentUser->school_id)
                ->where('type', $type)
                ->where('is_default', true)
                ->first();

            if ($template) {
                return $template;
            }
        }

        return DocumentTemplate::where('user_id', $currentUser->id)
            ->where('type', $type)
            ->where('is_default', true)
            ->first();
    }
}
