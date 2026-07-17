<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Kelola template Word custom (placeholder-based) untuk export soal.
 *
 * Bisa dipakai oleh:
 * - School Admin: template berlaku untuk semua guru di sekolahnya
 * - Individual: template personal miliknya sendiri (tidak punya sekolah/admin)
 *
 * Guru TIDAK memiliki akses ke controller ini (diblokir middleware
 * `role:school_admin,individual` di routes/web.php) — guru tidak perlu tahu
 * soal pengelolaan template. Saat export, guru otomatis mendapat dokumen
 * dengan template default sekolahnya, lihat BankSoalController::exportWithTemplate().
 */
class DocumentTemplateController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $templates = DocumentTemplate::query()
            ->when($user->isSchoolAdmin(), fn ($q) => $q->where('school_id', $user->school_id))
            ->when(! $user->isSchoolAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->get();

        return view('templates.index', compact('templates'));
    }

    public function create()
    {
        return view('templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:guru,siswa',
            'is_default' => 'nullable|boolean',
            'template' => [
                'required',
                'file',
                'max:5120',
                'mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ]);

        $user = auth()->user();
        $file = $request->file('template');
        $path = $file->store('document-templates', 'local');

        $isDefault = $request->boolean('is_default');

        // Jika set sebagai default, matikan default lain dengan type yang sama
        if ($isDefault) {
            DocumentTemplate::query()
                ->when($user->isSchoolAdmin(), fn ($q) => $q->where('school_id', $user->school_id))
                ->when(! $user->isSchoolAdmin(), fn ($q) => $q->where('user_id', $user->id))
                ->where('type', $request->type)
                ->update(['is_default' => false]);
        }

        $template = DocumentTemplate::create([
            'school_id' => $user->isSchoolAdmin() ? $user->school_id : null,
            'user_id' => $user->isSchoolAdmin() ? null : $user->id,
            'name' => $request->name,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'type' => $request->type,
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

        return redirect()
            ->route('templates.index')
            ->with('success', 'Template berhasil diupload.');
    }

    public function destroy(DocumentTemplate $template)
    {
        $user = auth()->user();

        $owns = $user->isSchoolAdmin()
            ? $template->school_id === $user->school_id
            : $template->user_id === $user->id;

        abort_unless($owns, 403);

        Storage::disk('local')->delete($template->file_path);

        AuditLogService::log(
            module: 'Template Dokumen',
            event: 'delete',
            description: "Menghapus template dokumen '{$template->name}'",
            properties: ['template_id' => $template->id, 'type' => $template->type]
        );

        $template->delete();

        return back()->with('success', 'Template berhasil dihapus.');
    }

    public function setDefault(DocumentTemplate $template)
    {
        $user = auth()->user();

        $owns = $user->isSchoolAdmin()
            ? $template->school_id === $user->school_id
            : $template->user_id === $user->id;

        abort_unless($owns, 403);

        DocumentTemplate::query()
            ->when($user->isSchoolAdmin(), fn ($q) => $q->where('school_id', $user->school_id))
            ->when(! $user->isSchoolAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->where('type', $template->type)
            ->update(['is_default' => false]);

        $template->update(['is_default' => true]);

        AuditLogService::log(
            module: 'Template Dokumen',
            event: 'set_default',
            description: "Menjadikan template '{$template->name}' sebagai default",
            properties: ['template_id' => $template->id, 'type' => $template->type]
        );

        return back()->with('success', "Template \"{$template->name}\" diset sebagai default.");
    }
}
