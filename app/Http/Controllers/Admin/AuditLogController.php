<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;

/**
 * Halaman audit log — super_admin bisa lihat seluruh platform (dengan filter
 * sekolah), school_admin HANYA bisa lihat log dari user di sekolahnya sendiri.
 */
class AuditLogController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $query = AuditLog::with(['user', 'school'])->latest();

        if ($user->isSchoolAdmin()) {
            // School admin tidak boleh menyentuh log sekolah lain, jadi
            // scope ini dipaksa di server — tidak boleh bisa dilewati lewat
            // query string manapun.
            abort_if(! $user->school_id, 403, 'Akun Anda tidak terhubung ke sekolah manapun.');

            $query->where('school_id', $user->school_id);
        }

        $schoolId = request('school_id');
        $module = request('module');
        $event = request('event');
        $userId = request('user_id');
        $search = request('search');
        $dateFrom = request('date_from');
        $dateTo = request('date_to');

        $query
            // Super admin saja yang boleh filter per sekolah — untuk school
            // admin, scope sekolahnya sudah dipaksa di atas.
            ->when($user->isSuperAdmin() && $schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($module, fn ($q) => $q->where('module', $module))
            ->when($event, fn ($q) => $q->where('event', $event))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($search, fn ($q) => $q->where('description', 'like', "%{$search}%"))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo));

        $auditLogs = $query->paginate(25)->withQueryString();

        // Opsi dropdown filter — module & event yang benar-benar pernah
        // tercatat, biar tidak menampilkan opsi kosong.
        $moduleOptions = AuditLog::query()
            ->when($user->isSchoolAdmin(), fn ($q) => $q->where('school_id', $user->school_id))
            ->distinct()->orderBy('module')->pluck('module');

        $eventOptions = AuditLog::query()
            ->when($user->isSchoolAdmin(), fn ($q) => $q->where('school_id', $user->school_id))
            ->distinct()->orderBy('event')->pluck('event');

        $userOptions = User::query()
            ->when($user->isSchoolAdmin(), fn ($q) => $q->where('school_id', $user->school_id))
            ->orderBy('name')->get(['id', 'name']);

        // Daftar sekolah hanya relevan buat super_admin (buat filter).
        $schoolOptions = $user->isSuperAdmin()
            ? School::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.audit-log.index', compact(
            'auditLogs', 'moduleOptions', 'eventOptions', 'userOptions', 'schoolOptions',
            'schoolId', 'module', 'event', 'userId', 'search', 'dateFrom', 'dateTo'
        ));
    }
}
