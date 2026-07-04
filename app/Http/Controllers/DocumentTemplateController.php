<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Kelola template Word custom (placeholder-based) untuk export soal.
 *
 * Bisa dipakai oleh:
 * - School Admin: template berlaku untuk semua guru di sekolahnya
 * - Guru/Individual: template personal miliknya sendiri
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

        // Guru: tampilkan juga template sekolah (read-only) supaya tidak
        // mengira export akan pakai format standar padahal sebenarnya
        // otomatis memakai template default sekolah.
        $schoolTemplates = collect();

        if (! $user->isSchoolAdmin() && $user->school_id) {
            $schoolTemplates = DocumentTemplate::where('school_id', $user->school_id)
                ->latest()
                ->get();
        }

        return view('templates.index', compact('templates', 'schoolTemplates'));
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

        DocumentTemplate::create([
            'school_id' => $user->isSchoolAdmin() ? $user->school_id : null,
            'user_id' => $user->isSchoolAdmin() ? null : $user->id,
            'name' => $request->name,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'type' => $request->type,
            'is_default' => $isDefault,
        ]);

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

        return back()->with('success', "Template \"{$template->name}\" diset sebagai default.");
    }
}
