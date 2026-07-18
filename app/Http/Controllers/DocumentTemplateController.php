<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Services\Document\DocumentTemplateService;
use Illuminate\Http\Request;

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
 *
 * Business logic (create/delete/set-default + audit log) ada di
 * DocumentTemplateService. Ownership check ada di DocumentTemplate model
 * (scope ownedBy() & method isOwnedBy()).
 */
class DocumentTemplateController extends Controller
{
    public function __construct(
        private readonly DocumentTemplateService $service,
    ) {}

    public function index()
    {
        $templates = DocumentTemplate::ownedBy(auth()->user())
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
        $validated = $request->validate([
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

        $this->service->store($validated, $request->file('template'), $request->user());

        return redirect()
            ->route('templates.index')
            ->with('success', 'Template berhasil diupload.');
    }

    public function destroy(DocumentTemplate $template)
    {
        $this->service->destroy($template, auth()->user());

        return back()->with('success', 'Template berhasil dihapus.');
    }

    public function setDefault(DocumentTemplate $template)
    {
        $this->service->setDefault($template, auth()->user());

        return back()->with('success', "Template \"{$template->name}\" diset sebagai default.");
    }
}
