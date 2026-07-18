<?php

namespace App\Services\Document;

use App\Models\DocumentTemplate;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Business logic untuk manajemen DocumentTemplate (upload, hapus, set
 * default). Ownership check ("apakah user ini boleh mengelola template
 * ini") ada di DocumentTemplate::isOwnedBy() — lihat App\Models\DocumentTemplate
 * — supaya tidak duplikat antara Controller dan Service ini.
 */
class DocumentTemplateService
{
    public function store(array $validated, UploadedFile $file, User $user): DocumentTemplate
    {
        $path = $file->store('document-templates', 'local');
        $isDefault = (bool) ($validated['is_default'] ?? false);

        // Jika set sebagai default, matikan default lain dengan type yang sama
        if ($isDefault) {
            DocumentTemplate::ownedBy($user)
                ->where('type', $validated['type'])
                ->update(['is_default' => false]);
        }

        $template = DocumentTemplate::create([
            'school_id' => $user->isSchoolAdmin() ? $user->school_id : null,
            'user_id' => $user->isSchoolAdmin() ? null : $user->id,
            'name' => $validated['name'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'type' => $validated['type'],
            'is_default' => $isDefault,
        ]);

        AuditLogService::log(
            module: 'Template Dokumen',
            event: 'create',
            description: "Membuat template dokumen '{$template->name}'",
            properties: [
                'template_id' => $template->id,
                'type' => $template->type,
                'is_default' => $isDefault,
            ]
        );

        return $template;
    }

    public function destroy(DocumentTemplate $template, User $user): void
    {
        abort_unless($template->isOwnedBy($user), 403);

        Storage::disk('local')->delete($template->file_path);

        AuditLogService::log(
            module: 'Template Dokumen',
            event: 'delete',
            description: "Menghapus template dokumen '{$template->name}'",
            properties: ['template_id' => $template->id, 'type' => $template->type]
        );

        $template->delete();
    }

    public function setDefault(DocumentTemplate $template, User $user): void
    {
        abort_unless($template->isOwnedBy($user), 403);

        DocumentTemplate::ownedBy($user)
            ->where('type', $template->type)
            ->update(['is_default' => false]);

        $template->update(['is_default' => true]);

        AuditLogService::log(
            module: 'Template Dokumen',
            event: 'set_default',
            description: "Menjadikan template '{$template->name}' sebagai default",
            properties: ['template_id' => $template->id, 'type' => $template->type]
        );
    }
}
