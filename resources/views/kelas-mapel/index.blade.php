<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full max-w-[1200px] mx-auto px-5 lg:px-8 py-8">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-1">Kelas &amp; Mapel</h1>
                <p class="text-sm text-slate-500">Daftar kelas dan mata pelajaran standar, beserta jumlah bank soal yang sudah kamu buat.</p>
            </div>

            {{-- Kelas --}}
            <div class="mb-10">
                <h2 class="text-sm font-bold text-slate-900 mb-4">Kelas</h2>

                @foreach($grades as $level => $items)
                <div class="mb-6">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ $level }}</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        @foreach($items as $item)
                        <a href="{{ route('generate-soal') }}"
                           class="bg-white rounded-xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-4 hover:ring-blue-200 transition">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $item['name'] }}</p>
                            <p class="text-xs text-slate-400 mt-1">{{ $item['total'] }} bank soal</p>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Mapel --}}
            <div>
                <h2 class="text-sm font-bold text-slate-900 mb-4">Mata Pelajaran</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    @foreach($subjects as $item)
                    <a href="{{ route('generate-soal') }}"
                       class="bg-white rounded-xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-4 hover:ring-blue-200 transition">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ $item['name'] }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $item['total'] }} bank soal</p>
                    </a>
                    @endforeach
                </div>

                @if($customSubjects->isNotEmpty())
                <div class="mt-6">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Mapel lain yang pernah kamu pakai</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                        @foreach($customSubjects as $item)
                        <a href="{{ route('generate-soal') }}"
                           class="bg-white rounded-xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-4 hover:ring-blue-200 transition">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $item['name'] }}</p>
                            <p class="text-xs text-slate-400 mt-1">{{ $item['total'] }} bank soal</p>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
