<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

            <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Bank Soal</h1>
                    <p class="text-slate-500 mt-2">Kelola semua bank soal yang sudah dibuat.</p>
                </div>

                <a href="{{ route('generate-soal') }}"
                   class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-semibold shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Generate Soal Baru
                </a>
            </div>

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-xl mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-6">
                <form method="GET" action="{{ route('bank-soal') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari judul, mapel, kelas, topik..."
                        class="border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

                    <select name="question_type"
                            class="border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Jenis</option>
                        <option value="multiple_choice" {{ request('question_type') == 'multiple_choice' ? 'selected' : '' }}>
                            Pilihan Ganda
                        </option>
                        <option value="essay" {{ request('question_type') == 'essay' ? 'selected' : '' }}>
                            Essay
                        </option>
                    </select>

                    <select name="difficulty"
                            class="border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Kesulitan</option>
                        <option value="mudah" {{ request('difficulty') == 'mudah' ? 'selected' : '' }}>Mudah</option>
                        <option value="sedang" {{ request('difficulty') == 'sedang' ? 'selected' : '' }}>Sedang</option>
                        <option value="sulit" {{ request('difficulty') == 'sulit' ? 'selected' : '' }}>Sulit</option>
                    </select>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-semibold">
                            Filter
                        </button>

                        @if(request('search') || request('question_type') || request('difficulty'))
                            <a href="{{ route('bank-soal') }}"
                               class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-3 rounded-xl font-semibold">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @if($questionSets->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-5">
                    @foreach($questionSets as $item)
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition p-5">
                            <div class="flex items-start justify-between gap-4 mb-5">
                                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                        <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>
                                    </svg>
                                </div>

                                @if($item->is_ai_generated)
                                <div class="flex flex-col items-end gap-1">

                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-violet-100 text-violet-700">
                                        AI Generated
                                    </span>

                                    <span class="text-xs text-slate-500">
                                        {{ ucfirst($item->ai_provider) }}
                                    </span>

                                </div>

                                @else

                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                    Manual
                                </span>

                                @endif
                            </div>

                            <h2 class="text-lg font-bold text-slate-900 mb-2">
                                {{ $item->title }}
                            </h2>

                            <p class="text-sm text-slate-500 mb-5">
                                {{ $item->topic }}
                            </p>

                            <div class="flex flex-wrap gap-2 mb-5">
                                <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-700">
                                    {{ $item->subject }}
                                </span>

                                <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-700">
                                    {{ $item->grade }}
                                </span>

                                <span class="px-3 py-1 text-xs rounded-full bg-indigo-100 text-indigo-700">
                                    {{ $item->question_type === 'multiple_choice' ? 'Pilihan Ganda' : 'Essay' }}
                                </span>

                                @if($item->difficulty === 'mudah')
                                    <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-700">Mudah</span>
                                @elseif($item->difficulty === 'sedang')
                                    <span class="px-3 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700">Sedang</span>
                                @else
                                    <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-700">Sulit</span>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-3 mb-5">
                                <div class="bg-slate-50 rounded-xl p-3">
                                    <p class="text-xs text-slate-500">Jumlah Soal</p>
                                    <p class="font-bold text-slate-900">{{ $item->total_questions }}</p>
                                </div>

                                <div class="bg-slate-50 rounded-xl p-3">
                                    <p class="text-xs text-slate-500">Tanggal</p>
                                    <p class="font-bold text-slate-900">{{ $item->created_at->format('d M Y') }}</p>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <a href="{{ route('bank-soal.show', $item->id) }}"
                                   class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-xl font-semibold">
                                    Detail
                                </a>

                                <a href="{{ route('bank-soal.edit', $item->id) }}"
                                   title="Generate Ulang — ubah parameter dan panggil AI lagi"
                                   class="inline-flex items-center justify-center w-12 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M23 4v6h-6"/>
                                        <path d="M1 20v-6h6"/>
                                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($questionSets->hasPages())
                    <div class="mt-6">
                        {{ $questionSets->onEachSide(1)->links() }}
                    </div>
                @endif
            @else
                <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
                    <h3 class="text-xl font-bold text-slate-900 mb-2">
                        Belum ada bank soal
                    </h3>

                    <p class="text-slate-500 mb-6">
                        Silakan buat bank soal pertama melalui halaman Generate Soal.
                    </p>

                    <a href="{{ route('generate-soal') }}"
                       class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-3 rounded-xl font-semibold hover:bg-blue-700">
                        Generate Soal Baru
                    </a>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>