<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * School Admin — kelola kop surat sekolah untuk export PDF/Word.
 */
class SchoolLetterheadController extends Controller
{
    public function edit()
    {
        $school = auth()->user()->school;

        abort_if(! $school, 404, 'Anda tidak terikat ke sekolah manapun.');

        return view('admin.letterhead.edit', compact('school'));
    }

    public function update(Request $request)
    {
        $school = auth()->user()->school;

        abort_if(! $school, 404);

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'letterhead_address' => 'nullable|string',
            'headmaster_name' => 'nullable|string|max:255',
            'headmaster_nip' => 'nullable|string|max:50',
            'show_letterhead_on_export' => 'nullable|boolean',
            'logo' => 'nullable|image|max:2048',
        ]);

        $trackedFields = [
            'name', 'address', 'letterhead_address',
            'headmaster_name', 'headmaster_nip', 'show_letterhead_on_export',
        ];
        $before = $school->only($trackedFields);

        $data = $request->only([
            'name', 'address', 'letterhead_address',
            'headmaster_name', 'headmaster_nip',
        ]);

        $data['show_letterhead_on_export'] = $request->boolean('show_letterhead_on_export');

        $logoChanged = false;

        if ($request->hasFile('logo')) {
            if ($school->logo) {
                Storage::disk('public')->delete($school->logo);
            }
            $data['logo'] = $request->file('logo')->store('school-logos', 'public');
            $logoChanged = true;
        }

        $school->update($data);

        $changes = AuditLogService::diff($before, $school->only($trackedFields));

        if ($logoChanged) {
            $changes['logo'] = ['before' => 'lama', 'after' => 'diganti'];
        }

        AuditLogService::log(
            module: 'Sekolah',
            event: 'update_letterhead',
            description: $changes
                ? "Mengubah kop surat sekolah '{$school->name}': ".implode(', ', array_keys($changes))
                : "Mengubah kop surat sekolah '{$school->name}' (tidak ada perubahan data)",
            properties: [
                'school_id' => $school->id,
                'changes' => $changes,
            ]
        );

        return back()->with('success', 'Kop surat sekolah berhasil diperbarui.');
    }
}
