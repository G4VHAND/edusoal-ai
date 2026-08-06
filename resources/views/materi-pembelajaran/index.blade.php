@php
    $currentUser = auth()->user();
@endphp

<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full max-w-[1200px] mx-auto px-5 lg:px-8 py-8">

            <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 mb-1">Materi Pembelajaran</h1>
                    <p class="text-sm text-slate-500">Unggah materi sebagai referensi AI supaya soal yang dihasilkan lebih akurat.</p>
                </div>
                <a href="{{ route('materi-pembelajaran.create') }}"
                   class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-semibold text-sm shadow-sm shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    Unggah Materi
                </a>
            </div>

            @if(session('success'))
            <div class="bg-emerald-50 text-emerald-700 text-sm font-medium px-4 py-3 rounded-xl mb-6">
                {{ session('success') }}
            </div>
            @endif

            {{-- Materi Sekolah (kalau user punya sekolah) --}}
            @if($currentUser->school_id)
            <div class="mb-8">
                <h2 class="text-sm font-bold text-slate-900 mb-1">Materi Sekolah</h2>
                <p class="text-xs text-slate-500 mb-4">Dibagikan oleh admin sekolah, bisa dipakai semua guru.</p>

                @if($school->isEmpty())
                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-8 text-center">
                    <p class="text-sm text-slate-400">Belum ada materi sekolah yang dibagikan.</p>
                </div>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($school as $material)
                        @include('materi-pembelajaran._card', ['material' => $material, 'badge' => 'Sekolah'])
                    @endforeach
                </div>
                @endif
            </div>
            @endif

            {{-- Materi Saya --}}
            <div>
                <h2 class="text-sm font-bold text-slate-900 mb-1">Materi Saya</h2>
                <p class="text-xs text-slate-500 mb-4">Materi pribadi, hanya kamu yang bisa lihat.</p>

                @if($personal->isEmpty())
                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-8 text-center">
                    <p class="text-sm text-slate-400 mb-3">Belum ada materi pribadi yang diunggah.</p>
                    <a href="{{ route('materi-pembelajaran.create') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 text-xs rounded-lg font-semibold hover:bg-blue-700">
                        Unggah Materi Pertama
                    </a>
                </div>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($personal as $material)
                        @include('materi-pembelajaran._card', ['material' => $material, 'badge' => 'Pribadi'])
                    @endforeach
                </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
