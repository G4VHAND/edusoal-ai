<?php

namespace App\Http\Controllers;

use App\Models\QuestionSet;
use Illuminate\Http\Request;

/**
 * Beda dengan Bank Soal (yang isinya cuma set soal yang berhasil jadi
 * "produk jadi"), Riwayat Generate menampilkan SEMUA percobaan generate
 * milik user — termasuk yang masih diproses, gagal (ai_error terisi),
 * atau dibatalkan (soft-deleted). Makanya query di sini pakai
 * withTrashed() dan tidak filter status sama sekali secara default.
 */
class RiwayatGenerateController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $questionSets = QuestionSet::withTrashed()
            ->where('user_id', auth()->id())
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('topic', 'like', "%{$search}%");
                });
            })
            ->when($status === 'dibatalkan', fn ($q) => $q->whereNotNull('deleted_at'))
            ->when($status && $status !== 'dibatalkan', fn ($q) => $q->whereNull('deleted_at')->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total' => (clone $this->baseQuery())->count(),
            'completed' => (clone $this->baseQuery())->where('status', 'completed')->count(),
            'failed' => (clone $this->baseQuery())->where('status', 'failed')->count(),
            'cancelled' => QuestionSet::onlyTrashed()->where('user_id', auth()->id())->count(),
        ];

        return view('riwayat-generate.index', compact('questionSets', 'status', 'search', 'summary'));
    }

    private function baseQuery()
    {
        return QuestionSet::where('user_id', auth()->id());
    }
}
