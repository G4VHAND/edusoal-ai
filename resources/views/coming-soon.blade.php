@php
    $labels = [
        'materi-pembelajaran' => ['title' => 'Materi Pembelajaran', 'desc' => 'Unggah dan kelola materi sebagai referensi AI supaya soal yang dihasilkan lebih akurat.'],
        'riwayat-generate' => ['title' => 'Riwayat Generate', 'desc' => 'Lihat log lengkap semua proses generate soal, termasuk yang gagal atau dibatalkan.'],
        'kelas-mapel' => ['title' => 'Kelas & Mapel', 'desc' => 'Kelola daftar kelas dan mata pelajaran supaya generate soal lebih terstruktur.'],
        'bantuan' => ['title' => 'Bantuan', 'desc' => 'Pusat bantuan, FAQ, dan cara menghubungi tim EduSoal AI.'],
    ];
    $info = $labels[$feature] ?? ['title' => str($feature)->replace('-', ' ')->title(), 'desc' => 'Fitur ini masih dalam pengembangan.'];
@endphp

<x-app-layout>
    <div class="min-h-screen bg-slate-50 flex items-center justify-center px-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-10 max-w-md w-full text-center">
            <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </div>
            <h1 class="text-lg font-bold text-slate-900 mb-2">{{ $info['title'] }}</h1>
            <p class="text-sm text-slate-500 mb-6">{{ $info['desc'] }}</p>
            <span class="inline-block text-xs font-semibold px-3 py-1.5 rounded-full bg-amber-50 text-amber-700 mb-6">
                Segera hadir
            </span>
            <a href="{{ route('dashboard') }}"
               class="block bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm py-2.5 rounded-xl transition">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</x-app-layout>