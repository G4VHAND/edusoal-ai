<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AI\AIServiceFactory;
use Illuminate\Http\Request;

/**
 * School Admin — pilih provider AI yang dipakai untuk generate soal di
 * sekolahnya. Guru TIDAK bisa memilih provider sendiri sama sekali —
 * apapun yang diatur di sini otomatis berlaku untuk semua guru di
 * sekolah tersebut (lihat User::resolveAiProvider()).
 */
class SchoolAIProviderController extends Controller
{
    public function edit()
    {
        $school = auth()->user()->school;

        abort_if(! $school, 404, 'Anda tidak terikat ke sekolah manapun.');

        $providers = AIServiceFactory::labeled();

        return view('admin.ai-provider.edit', compact('school', 'providers'));
    }

    public function update(Request $request)
    {
        $school = auth()->user()->school;

        abort_if(! $school, 404);

        $validated = $request->validate([
            'ai_provider' => 'required|string|in:'.implode(',', AIServiceFactory::supported()),
        ]);

        $school->update(['ai_provider' => $validated['ai_provider']]);

        return back()->with('success', 'Provider AI sekolah berhasil diperbarui. Semua guru di sekolah ini akan otomatis memakai provider ini untuk generate soal berikutnya.');
    }
}
