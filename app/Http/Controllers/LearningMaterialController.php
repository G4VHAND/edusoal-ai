<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use App\Services\Document\LearningMaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LearningMaterialController extends Controller
{
    public function __construct(
        private readonly LearningMaterialService $service,
    ) {}

    public function index()
    {
        $materials = LearningMaterial::visibleTo(auth()->user())
            ->with('user:id,name')
            ->latest()
            ->get();

        $personal = $materials->whereNull('school_id');
        $school = $materials->whereNotNull('school_id');

        return view('materi-pembelajaran.index', compact('personal', 'school'));
    }

    public function create()
    {
        return view('materi-pembelajaran.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'visibility' => 'nullable|in:pribadi,sekolah',
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,txt',
            ],
        ]);

        $this->service->store($validated, $request->file('file'), $request->user());

        return redirect()
            ->route('materi-pembelajaran.index')
            ->with('success', 'Materi berhasil diunggah.');
    }

    public function download(LearningMaterial $material)
    {
        $user = auth()->user();
        $canAccess = $material->user_id === $user->id
            || ($material->school_id && $material->school_id === $user->school_id);

        abort_unless($canAccess, 403);

        return Storage::disk('local')
            ->download($material->file_path, $material->original_filename);
    }

    public function destroy(LearningMaterial $material)
    {
        $this->service->destroy($material, auth()->user());

        return back()->with('success', 'Materi berhasil dihapus.');
    }
}
