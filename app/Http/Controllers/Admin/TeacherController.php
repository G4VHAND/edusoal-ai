<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Digunakan oleh school_admin untuk mengelola guru di sekolahnya.
 */
class TeacherController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $schoolId = $user->isSuperAdmin() ? null : $user->school_id;

        $teachers = User::where('role', 'teacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->withCount('questionSets')
            ->latest()
            ->paginate(20);

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create()
    {
        $user = auth()->user();
        $schools = $user->isSuperAdmin()
            ? School::where('is_active', true)->get()
            : collect([$user->school]);

        return view('admin.teachers.create', compact('schools'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8',
            'school_id' => $user->isSuperAdmin() ? 'required|exists:schools,id' : 'nullable',
        ]);

        $schoolId = $user->isSuperAdmin()
            ? $request->school_id
            : $user->school_id;

        if (! $schoolId) {
            return back()->withErrors(['school_id' => 'Sekolah tidak ditemukan.']);
        }

        // Cek batas jumlah guru di plan sekolah
        $school = School::findOrFail($schoolId);
        $plan   = $school->activeSubscription?->plan;

        if ($plan && ! $plan->isUnlimitedTeachers()) {
            $currentCount = $school->teachers()->count();
            if ($currentCount >= $plan->max_teachers) {
                return back()->withErrors([
                    'limit' => "Sekolah ini sudah mencapai batas maksimal {$plan->max_teachers} guru sesuai paket {$plan->name}. Upgrade paket untuk menambah guru.",
                ]);
            }
        }

        User::create([
            'name'                 => $request->name,
            'email'                => $request->email,
            'password'             => Hash::make($request->password),
            'school_id'            => $schoolId,
            'subscription_plan_id' => $plan?->id,
            'role'                 => 'teacher',
            'email_verified_at'    => now(),
            'is_active'            => true,
            'quota_reset_at'       => now()->startOfMonth()->addMonth(),
        ]);

        return redirect()
            ->route('admin.teachers.index')
            ->with('success', 'Akun guru berhasil dibuat.');
    }

    public function destroy(User $user)
    {
        // School admin hanya bisa hapus guru di sekolahnya
        if (auth()->user()->isSchoolAdmin()
            && $user->school_id !== auth()->user()->school_id) {
            abort(403);
        }

        $user->delete();

        return back()->with('success', 'Akun guru berhasil dihapus.');
    }
}
