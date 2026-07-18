<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;

/**
 * Controller ini sengaja tipis — semua query, cache, dan transform data
 * chart ada di DashboardService. Lihat DashboardService untuk detail
 * kenapa masing-masing query dibentuk seperti itu (mis. kenapa tidak
 * pakai MONTH() di SQL).
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        // Super admin & school admin tidak generate soal sendiri (diblokir
        // role:teacher,individual di route /generate-soal), jadi dashboard
        // personal ini akan selalu kosong buat mereka + tombol "Generate
        // Soal" di dalamnya akan 403 kalau diklik. Lempar ke dashboard
        // admin mereka yang sebenarnya, supaya tidak jadi dead-end.
        if ($user->isSuperAdmin() || $user->isSchoolAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $period = $request->get('period', 'all');

        return view('dashboard', $this->service->forUser($user, $period));
    }
}
