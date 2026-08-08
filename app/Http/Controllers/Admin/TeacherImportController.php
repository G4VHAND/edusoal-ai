<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Import guru massal lewat CSV, alternatif dari TeacherController::store()
 * yang cuma bisa satu-satu. Format CSV sengaja dibuat sesederhana mungkin:
 * cuma kolom "name" dan "email" — password digenerate random per baris dan
 * ditampilkan di halaman hasil supaya admin bisa dibagikan manual (belum
 * ada sistem kirim email undangan otomatis di aplikasi ini).
 */
class TeacherImportController extends Controller
{
    public function create()
    {
        return view('admin.teachers.import');
    }

    /**
     * Sediakan file CSV contoh supaya format kolomnya jelas.
     */
    public function template()
    {
        $content = "name,email\nBudi Santoso,budi.santoso@sekolah.sch.id\nSiti Aisyah,siti.aisyah@sekolah.sch.id\n";

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template-import-guru.csv"',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $school = School::findOrFail(auth()->user()->school_id);
        $plan = $school->activeSubscription()->first()?->plan;
        $currentCount = $school->teachers()->count();
        $maxTeachers = $plan && ! $plan->isUnlimitedTeachers() ? $plan->max_teachers : null;

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($h) => strtolower(trim($h)), fgetcsv($handle) ?: []);

        $nameIdx = array_search('name', $header);
        $emailIdx = array_search('email', $header);

        if ($nameIdx === false || $emailIdx === false) {
            fclose($handle);

            return back()->withErrors(['file' => 'Format CSV tidak sesuai. Kolom "name" dan "email" wajib ada — unduh template dulu kalau ragu.']);
        }

        $created = [];
        $failed = [];
        $seenEmails = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $name = trim($row[$nameIdx] ?? '');
            $email = trim(strtolower($row[$emailIdx] ?? ''));

            if ($name === '' || $email === '') {
                $failed[] = ['row' => $rowNumber, 'email' => $email ?: '-', 'reason' => 'Nama atau email kosong'];

                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed[] = ['row' => $rowNumber, 'email' => $email, 'reason' => 'Format email tidak valid'];

                continue;
            }

            if (isset($seenEmails[$email]) || User::where('email', $email)->exists()) {
                $failed[] = ['row' => $rowNumber, 'email' => $email, 'reason' => 'Email sudah terdaftar / duplikat'];

                continue;
            }

            if ($maxTeachers !== null && ($currentCount + count($created)) >= $maxTeachers) {
                $failed[] = ['row' => $rowNumber, 'email' => $email, 'reason' => "Melebihi batas {$maxTeachers} guru sesuai paket {$plan->name}"];

                continue;
            }

            $password = Str::password(10, symbols: false);
            $seenEmails[$email] = true;

            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'school_id' => $school->id,
                'subscription_plan_id' => $plan?->id,
                'role' => 'teacher',
                'email_verified_at' => now(),
                'is_active' => true,
                'quota_reset_at' => now()->startOfMonth()->addMonth(),
            ]);

            $created[] = ['name' => $name, 'email' => $email, 'password' => $password];
        }

        fclose($handle);

        if (count($created) > 0) {
            AuditLogService::log(
                module: 'Manajemen Guru',
                event: 'create',
                description: count($created).' guru berhasil diimport dari CSV',
                properties: ['count' => count($created), 'failed_count' => count($failed)]
            );
        }

        return view('admin.teachers.import-result', compact('created', 'failed'));
    }
}
