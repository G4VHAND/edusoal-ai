@php
    $statusMeta = [
        'completed' => ['label' => 'Selesai', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500'],
        'processing' => ['label' => 'Diproses', 'bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'dot' => 'bg-blue-500'],
        'pending' => ['label' => 'Menunggu', 'bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'dot' => 'bg-amber-500'],
        'failed' => ['label' => 'Gagal', 'bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'dot' => 'bg-rose-500'],
        'dibatalkan' => ['label' => 'Dibatalkan', 'bg' => 'bg-slate-100', 'text' => 'text-slate-500', 'dot' => 'bg-slate-400'],
    ];

    $filters = [
        '' => 'Semua',
        'completed' => 'Selesai',
        'processing' => 'Diproses',
        'pending' => 'Menunggu',
        'failed' => 'Gagal',
        'dibatalkan' => 'Dibatalkan',
    ];
@endphp

<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full max-w-[1440px] mx-auto px-5 lg:px-8 py-8">

            <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 mb-1">Riwayat Generate</h1>
                    <p class="text-sm text-slate-500">Log lengkap semua proses generate soal, termasuk yang gagal atau dibatalkan.</p>
                </div>
                <a href="{{ route('generate-soal') }}"
                   class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-semibold text-sm shadow-sm shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    Generate Soal Baru
                </a>
            </div>

            {{-- Summary cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <p class="text-xs text-slate-500 mb-1">Total Percobaan</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $summary['total'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <p class="text-xs text-slate-500 mb-1">Selesai</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ $summary['completed'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <p class="text-xs text-slate-500 mb-1">Gagal</p>
                    <p class="text-2xl font-bold text-rose-600">{{ $summary['failed'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <p class="text-xs text-slate-500 mb-1">Dibatalkan</p>
                    <p class="text-2xl font-bold text-slate-500">{{ $summary['cancelled'] }}</p>
                </div>
            </div>

            {{-- Filter + search --}}
            <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 mb-6">
                <form method="GET" action="{{ route('riwayat-generate') }}" class="flex flex-wrap items-center gap-3">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari judul, mapel, atau topik..."
                           class="flex-1 min-w-[200px] border border-slate-200 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

                    <div class="flex flex-wrap gap-2">
                        @foreach($filters as $value => $label)
                        <a href="{{ route('riwayat-generate', array_filter(['status' => $value, 'search' => $search])) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition
                           {{ ($status ?? '') === $value ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            {{ $label }}
                        </a>
                        @endforeach
                    </div>

                    <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-800">
                        Cari
                    </button>
                </form>
            </div>

            {{-- List --}}
            <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 overflow-hidden">
                @forelse($questionSets as $qs)
                    @php
                        $metaKey = $qs->trashed() ? 'dibatalkan' : ($qs->status ?? 'pending');
                        $meta = $statusMeta[$metaKey] ?? $statusMeta['pending'];
                        $canOpen = !$qs->trashed() && $qs->status === 'completed';
                    @endphp
                    <div class="flex items-center gap-4 px-5 py-4 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $meta['dot'] }}"></span>

                        <div class="min-w-0 flex-1">
                            @if($canOpen)
                            <a href="{{ route('bank-soal.show', $qs->id) }}" class="text-sm font-semibold text-slate-900 hover:text-blue-600 truncate block">
                                {{ $qs->title }}
                            </a>
                            @else
                            <p class="text-sm font-semibold text-slate-900 truncate">{{ $qs->title }}</p>
                            @endif
                            <p class="text-xs text-slate-500 truncate">
                                {{ $qs->subject }} · {{ $qs->grade }} · {{ $qs->total_questions }} soal
                                @if($qs->ai_provider) · {{ ucfirst($qs->ai_provider) }} @endif
                            </p>
                            @if($metaKey === 'failed' && $qs->ai_error)
                            <p class="text-[11px] text-rose-500 mt-1 truncate" title="{{ $qs->ai_error }}">
                                {{ Str::limit($qs->ai_error, 90) }}
                            </p>
                            @endif
                        </div>

                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full shrink-0 {{ $meta['bg'] }} {{ $meta['text'] }}">
                            {{ $meta['label'] }}
                        </span>

                        <span class="text-xs text-slate-400 shrink-0 w-24 text-right">
                            {{ $qs->created_at->locale('id')->diffForHumans(null, true) }}
                        </span>
                    </div>
                @empty
                    <div class="text-center py-16">
                        <p class="text-sm font-semibold text-slate-900 mb-1">Belum ada riwayat generate</p>
                        <p class="text-xs text-slate-500 mb-4">Semua percobaan generate soal kamu akan muncul di sini.</p>
                        <a href="{{ route('generate-soal') }}"
                           class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 text-xs rounded-lg font-semibold hover:bg-blue-700">
                            Generate Soal Pertama
                        </a>
                    </div>
                @endforelse
            </div>

            @if($questionSets->hasPages())
            <div class="mt-6">
                {{ $questionSets->links() }}
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
