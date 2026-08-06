<?php

namespace App\Services\Document;

use App\Models\LearningMaterial;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LearningMaterialService
{
    public function store(array $validated, UploadedFile $file, User $user): LearningMaterial
    {
        // Cuma school_admin yang boleh bikin materi SEKOLAH (shared). Guru dan
        // individual — apapun yang mereka kirim di form — selalu jadi materi
        // pribadi. Ini validasi server-side, bukan cuma disembunyikan di UI.
        $isSchoolMaterial = $user->isSchoolAdmin() && ($validated['visibility'] ?? 'pribadi') === 'sekolah';

        $path = $file->store('learning-materials', 'local');

        $material = LearningMaterial::create([
            'user_id' => $user->id,
            'school_id' => $isSchoolMaterial ? $user->school_id : null,
            'title' => $validated['title'],
            'subject' => $validated['subject'] ?? null,
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
        ]);

        AuditLogService::log(
            module: 'Materi Pembelajaran',
            event: 'create',
            description: "Mengunggah materi '{$material->title}'".($isSchoolMaterial ? ' (dibagikan ke sekolah)' : ' (pribadi)'),
            properties: [
                'material_id' => $material->id,
                'shared_to_school' => $isSchoolMaterial,
            ]
        );

        return $material;
    }

    public function destroy(LearningMaterial $material, User $user): void
    {
        abort_unless($material->isOwnedBy($user), 403);

        Storage::disk('local')->delete($material->file_path);

        AuditLogService::log(
            module: 'Materi Pembelajaran',
            event: 'delete',
            description: "Menghapus materi '{$material->title}'",
            properties: ['material_id' => $material->id]
        );

        $material->delete();
    }
}
