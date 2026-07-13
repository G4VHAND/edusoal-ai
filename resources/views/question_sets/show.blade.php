<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="max-w-6xl mx-auto px-6 py-8">

            <div class="mb-6">
                <a href="{{ route('bank-soal') }}"
                   class="inline-flex items-center gap-2 text-blue-600 font-semibold mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                    Kembali ke Bank Soal
                </a>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="flex flex-wrap justify-between items-start gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-slate-900">
                                {{ $questionSet->title }}
                            </h1>

                            <p class="text-slate-500 mt-2">
                                {{ $questionSet->subject }} • {{ $questionSet->grade }} • {{ $questionSet->topic }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">

                            {{-- Generate Ulang (dulu bernama "Edit") — ubah parameter & panggil AI
                                 lagi. Untuk perbaikan teks langsung tanpa AI, pakai ikon pensil
                                 di masing-masing soal. --}}
                            <a href="{{ route('bank-soal.edit', $questionSet->id) }}"
                                title="Ubah parameter (mapel, kelas, jumlah soal, dst.) dan generate ulang lewat AI"
                                class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-5 py-3 rounded-xl font-semibold shadow-sm">

                                    <svg class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path d="M23 4v6h-6"/>
                                        <path d="M1 20v-6h6"/>
                                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                                    </svg>

                                    Generate Ulang
                            </a>

                            {{-- Hapus --}}
                            <form action="{{ route('bank-soal.destroy', $questionSet->id) }}"
                                method="POST"
                                onsubmit="return confirm('Yakin ingin menghapus bank soal ini?')">

                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-5 py-3 rounded-xl font-semibold shadow-sm">

                                    <svg class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path d="M3 6h18"/>
                                        <path d="M8 6V4h8v2"/>
                                        <path d="M19 6l-1 14H6L5 6"/>
                                        <path d="M10 11v6"/>
                                        <path d="M14 11v6"/>
                                    </svg>

                                    Hapus
                                </button>
                            </form>

                            {{-- Export Dropdown --}}
                            <div class="relative" x-data="{ open: false }">

                                <button type="button"
                                        @click="open = !open"
                                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-semibold shadow-sm">

                                    <svg class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <path d="M7 10l5 5 5-5"/>
                                        <path d="M12 15V3"/>
                                    </svg>

                                    Export

                                    <svg class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path d="M6 9l6 6 6-6"/>
                                    </svg>

                                </button>

                                <div x-show="open"
                                    @click.outside="open = false"
                                    x-transition
                                    class="absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-xl shadow-lg z-50 overflow-hidden">

                                    <a href="{{ route('bank-soal.export-student-pdf', $questionSet->id) }}"
                                        class="flex items-center gap-3 px-4 py-3 text-slate-700 hover:bg-slate-100">

                                            <svg class="w-5 h-5 text-blue-600"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <path d="M14 2v6h6"/>
                                            </svg>

                                            <div>
                                                <p class="font-medium">PDF Soal</p>
                                                <p class="text-xs text-slate-500">Versi siswa</p>
                                            </div>

                                    </a>

                                    <a href="{{ route('bank-soal.export-pdf', $questionSet->id) }}"
                                    class="flex items-center gap-3 px-4 py-3 text-slate-700 hover:bg-slate-100">
                                        <svg class="w-5 h-5 text-blue-600"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <path d="M14 2v6h6"/>
                                        </svg>

                                        <div>
                                            <p class="font-medium">PDF Kunci Guru</p>
                                            <p class="text-xs text-slate-500">Versi guru</p>
                                        </div>
                                    </a>

                                    <div class="border-t border-slate-100 my-1"></div>

                                    {{-- Word — otomatis pakai kop surat & format sekolah kalau tersedia,
                                         fallback ke format standar kalau tidak ada. Guru tidak perlu
                                         memilih atau tahu soal "template" sama sekali. --}}
                                    <a href="{{ route('bank-soal.export-template', ['questionSet' => $questionSet->id, 'type' => 'guru']) }}"
                                    class="flex items-center gap-3 px-4 py-3 text-slate-700 hover:bg-slate-100">
                                        <svg class="w-5 h-5 text-blue-600"
                                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <path d="M14 2v6h6"/>
                                        </svg>
                                        <div>
                                            <p class="font-medium">Word Kunci Guru</p>
                                            <p class="text-xs text-slate-500">Versi guru, lengkap dengan jawaban</p>
                                        </div>
                                    </a>

                                    <a href="{{ route('bank-soal.export-template', ['questionSet' => $questionSet->id, 'type' => 'siswa']) }}"
                                    class="flex items-center gap-3 px-4 py-3 text-slate-700 hover:bg-slate-100">
                                        <svg class="w-5 h-5 text-blue-600"
                                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <path d="M14 2v6h6"/>
                                        </svg>
                                        <div>
                                            <p class="font-medium">Word Soal Siswa</p>
                                            <p class="text-xs text-slate-500">Versi siswa</p>
                                        </div>
                                    </a>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                    <p class="text-slate-500 text-sm mb-2">Jenis Soal</p>
                    <p class="font-bold text-slate-900">
                        {{ $questionSet->question_type === 'multiple_choice' ? 'Pilihan Ganda' : 'Essay' }}
                    </p>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                    <p class="text-slate-500 text-sm mb-2">Kesulitan</p>
                    <p class="font-bold text-slate-900 capitalize">
                        {{ $questionSet->difficulty }}
                    </p>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                    <p class="text-slate-500 text-sm mb-2">Kurikulum</p>
                    <p class="font-bold text-slate-900">
                        {{ $questionSet->curriculum === 'k13' ? 'K13' : 'Merdeka' }}
                    </p>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                    <p class="text-slate-500 text-sm mb-2">Jenis Asesmen</p>
                    <p class="font-bold text-slate-900 uppercase">
                        {{ $questionSet->assessment_type === 'reguler' ? 'Reguler' : $questionSet->assessment_type }}
                    </p>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                    <p class="text-slate-500 text-sm mb-2">Jumlah Soal</p>
                    <p class="font-bold text-slate-900">
                        {{ $questionSet->total_questions }} soal
                    </p>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                    <p class="text-slate-500 text-sm mb-2">Dibuat Pada</p>
                    <p class="font-bold text-slate-900">
                        {{ $questionSet->created_at->format('d M Y') }}
                    </p>
                </div>
            </div>

            {{-- Banner status — di luar grid 6 kolom di atas supaya selalu full-width,
                 tidak lagi ikut kepencet jadi 1 kolom sempit seperti sebelumnya. --}}
            <div class="space-y-3 mb-8">

                @if(in_array($questionSet->status, ['pending', 'processing']))
                {{-- STATUS: Sedang diproses — tampilkan loading, polling setiap 3 detik --}}
                <div id="processing-banner"
                     class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-amber-600 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-amber-800">AI sedang memproses soal...</p>
                            <p class="text-sm text-amber-600">Halaman akan otomatis diperbarui saat selesai.</p>
                        </div>
                    </div>
                </div>

                @elseif($questionSet->status === 'failed')
                {{-- STATUS: Gagal --}}
                <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 8v4M12 16h.01"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-red-800">AI gagal memproses soal</p>
                            <p class="text-sm text-red-600 mt-1">{{ $questionSet->ai_error }}</p>
                        </div>
                    </div>
                </div>

                @elseif($questionSet->is_ai_generated)
                {{-- STATUS: Selesai — gabungkan badge "AI Generated" + disclaimer review
                     jadi satu kartu supaya tidak berantakan jadi 2 kotak terpisah --}}
                <div class="bg-gradient-to-br from-violet-50 to-white border border-violet-200 rounded-2xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-violet-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z"/>
                            </svg>
                        </div>

                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <p class="font-bold text-violet-900">Dibuat dengan AI</p>
                                @if($questionSet->material_image)
                                    <span class="text-xs font-medium text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full">
                                        📷 Vision AI dari gambar
                                    </span>
                                @endif
                            </div>

                            <p class="text-sm text-violet-800/80 mt-2 leading-relaxed">
                                Soal ini digenerate AI dan mungkin mengandung ketidakakuratan. Setiap soal
                                menyertakan kolom <strong class="text-violet-900">Sumber</strong> yang
                                menunjukkan dasar pembuatannya — harap periksa tiap soal sebelum
                                digunakan dalam ujian.
                            </p>

                            @if($questionSet->source_reference)
                                <div class="mt-3 pt-3 border-t border-violet-200/70">
                                    <p class="text-xs font-semibold text-violet-600 uppercase tracking-wide">Referensi Utama</p>
                                    <p class="text-sm text-violet-900 mt-0.5">{{ $questionSet->source_reference }}</p>

                                    {{-- Link pencarian DIBANGUN SENDIRI dari teks referensi (bukan
                                         link buatan AI) — jadi tidak ada risiko link mati/karangan,
                                         karena Google yang menampilkan hasil pencariannya. --}}
                                    <div class="flex items-center gap-3 mt-2">
                                        <a href="https://www.google.com/search?q={{ urlencode($questionSet->source_reference) }}"
                                           target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 text-xs font-medium text-violet-700 hover:text-violet-900 hover:underline">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <circle cx="11" cy="11" r="8"/>
                                                <path d="M21 21l-4.35-4.35"/>
                                            </svg>
                                            Cari di Google
                                        </a>
                                        <a href="https://scholar.google.com/scholar?q={{ urlencode($questionSet->source_reference) }}"
                                           target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 text-xs font-medium text-violet-700 hover:text-violet-900 hover:underline">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <circle cx="11" cy="11" r="8"/>
                                                <path d="M21 21l-4.35-4.35"/>
                                            </svg>
                                            Cari di Google Scholar
                                        </a>
                                        <a href="https://www.youtube.com/results?search_query={{ urlencode($questionSet->source_reference) }}"
                                           target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 text-xs font-medium text-violet-700 hover:text-violet-900 hover:underline">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path d="M22 8s-.2-1.4-.8-2c-.8-.8-1.6-.8-2-.9C16.4 5 12 5 12 5s-4.4 0-7.2.1c-.4 0-1.2.1-2 .9C2.2 6.6 2 8 2 8S1.8 9.6 1.8 11.3v1.4C1.8 14.4 2 16 2 16s.2 1.4.8 2c.8.8 1.8.7 2.3.8C7 19 12 19 12 19s4.4 0 7.2-.1c.4 0 1.2-.1 2-.8.6-.6.8-2 .8-2s.2-1.6.2-3.3v-1.4C22.2 9.6 22 8 22 8z"/>
                                                <path d="M10 14.6V9.4l5 2.6-5 2.6z" fill="white" stroke="none"/>
                                            </svg>
                                            Cari di YouTube
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                @if($questionSet->material_file)
                <div class="bg-white rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <path d="M14 2v6h6"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 text-sm">Materi Pembelajaran</p>
                            <p class="text-slate-500 text-sm">
                                {{ $questionSet->material_original_name ?? basename($questionSet->material_file) }}
                            </p>
                        </div>
                    </div>
                </div>
                @endif

            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                @if($questionSet->ai_error)

                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mb-6">

                    <strong>AI Error:</strong>

                    <br>

                    {{ $questionSet->ai_error }}

                </div>

                @endif
                <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">
                            Daftar Pertanyaan
                        </h2>
                        <p class="text-slate-500 text-sm">
                            Pertanyaan hasil generate akan ditampilkan pada bagian ini.
                        </p>
                    </div>
                </div>

                @if($errors->has('question'))
                    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 mb-4 text-sm">
                        {{ $errors->first('question') }}
                    </div>
                @endif

                @forelse($questionSet->questions as $index => $question)
                    <div class="border border-slate-200 rounded-xl p-5 mb-4
                        {{ $question->needsImageUpload() ? 'border-amber-300 bg-amber-50/30' : '' }}">

                        <div class="flex items-start justify-between gap-3 mb-3">
                            <p class="text-slate-900 text-justify">
                                <strong>{{ $index + 1 }}.</strong> {!! \App\Services\Document\TextFormatter::toHtml($question->question_text) !!}
                            </p>

                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('questions.edit', $question->id) }}"
                                   title="Edit soal ini"
                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </a>

                                <button type="button"
                                        title="Hapus soal ini"
                                        x-data=""
                                        x-on:click="$dispatch('open-modal', 'confirm-delete-question-{{ $question->id }}')"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M3 6h18"/>
                                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/>
                                        <path d="M10 11v6M14 11v6"/>
                                    </svg>
                                </button>

                                <x-modal name="confirm-delete-question-{{ $question->id }}" focusable>
                                    <div class="p-6">
                                        <div class="flex items-start gap-4">
                                            <div class="w-11 h-11 rounded-full bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path d="M3 6h18"/>
                                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/>
                                                    <path d="M10 11v6M14 11v6"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h2 class="text-lg font-bold text-slate-900">
                                                    Hapus soal nomor {{ $index + 1 }}?
                                                </h2>
                                                <p class="text-slate-500 text-sm mt-1">
                                                    Tindakan ini tidak bisa dibatalkan. Soal beserta jawaban dan
                                                    pembahasannya akan dihapus permanen.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-6 flex justify-end gap-3">
                                            <button type="button"
                                                    x-on:click="$dispatch('close')"
                                                    class="px-5 py-2.5 rounded-xl font-semibold text-slate-600 hover:bg-slate-100 transition">
                                                Batal
                                            </button>

                                            <form method="POST"
                                                  action="{{ route('bank-soal.question.destroy', [$questionSet->id, $question->id]) }}">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        class="px-5 py-2.5 rounded-xl font-semibold bg-red-600 text-white hover:bg-red-700 transition">
                                                    Ya, Hapus Soal
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </x-modal>
                            </div>
                        </div>

                        {{-- Gambar jika sudah diupload --}}
                        @if($question->hasImage())
                            <div class="mb-4">
                                <img src="{{ route('bank-soal.question.image.serve', [$questionSet->id, $question->id]) }}"
                                     alt="Gambar soal {{ $index + 1 }}"
                                     class="max-h-56 rounded-xl border border-slate-200 object-contain bg-white">
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-xs text-green-600 font-medium">✅ Gambar terpasang</span>
                                    <form method="POST"
                                          action="{{ route('bank-soal.question.image.delete', [$questionSet->id, $question->id]) }}"
                                          class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('Hapus gambar ini?')"
                                                class="text-xs text-red-500 hover:underline">
                                            Hapus gambar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif

                        @if($questionSet->question_type === 'multiple_choice')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-slate-700">
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                    A. {!! \App\Services\Document\TextFormatter::toHtml($question->option_a) !!}
                                </div>
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                    B. {!! \App\Services\Document\TextFormatter::toHtml($question->option_b) !!}
                                </div>
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                    C. {!! \App\Services\Document\TextFormatter::toHtml($question->option_c) !!}
                                </div>
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                    D. {!! \App\Services\Document\TextFormatter::toHtml($question->option_d) !!}
                                </div>
                            </div>
                        @endif

                        <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3">
                            <p class="text-green-700 text-justify">
                                <strong>Jawaban:</strong> {!! \App\Services\Document\TextFormatter::toHtml($question->correct_answer) !!}
                            </p>
                        </div>

                        @if($question->explanation)
                            <div class="mt-3 bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <p class="text-blue-700 text-justify">
                                    <span class="font-semibold">Pembahasan:</span>
                                    {!! \App\Services\Document\TextFormatter::toHtml($question->explanation) !!}
                                </p>
                            </div>
                        @endif

                        @if($question->source_paragraph)
                            <div class="mt-2 bg-slate-50 border border-slate-200 rounded-lg p-3">
                                <p class="text-slate-500 text-xs">
                                    <span class="font-semibold text-slate-600">📎 Sumber:</span>
                                    {!! \App\Services\Document\TextFormatter::toHtml($question->source_paragraph) !!}
                                </p>
                            </div>
                        @endif

                        {{-- Panel rekomendasi gambar dari AI --}}
                        @if($question->needs_image)
                            <div class="mt-3 border border-amber-200 bg-amber-50 rounded-xl p-4">
                                <div class="flex items-start gap-3">
                                    <span class="text-amber-500 text-lg flex-shrink-0">📷</span>
                                    <div class="flex-1">
                                        <p class="text-amber-800 font-semibold text-sm">
                                            AI merekomendasikan gambar untuk soal ini
                                        </p>
                                        @if($question->image_recommendation)
                                            <p class="text-amber-700 text-sm mt-1">
                                                <span class="font-medium">Gambar yang dibutuhkan:</span>
                                                {{ $question->image_recommendation }}
                                            </p>
                                            <a href="https://www.google.com/search?tbm=isch&q={{ urlencode($question->image_recommendation) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1.5 mt-2 text-xs bg-white border border-amber-300
                                                      text-amber-700 px-3 py-1.5 rounded-lg hover:bg-amber-50 transition font-medium">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                                </svg>
                                                Cari di Google Images
                                            </a>
                                        @endif

                                        @if(! $question->hasImage())
                                            {{-- Form upload gambar --}}
                                            <form method="POST"
                                                  action="{{ route('bank-soal.question.image.upload', [$questionSet->id, $question->id]) }}"
                                                  enctype="multipart/form-data"
                                                  class="mt-3 flex items-center gap-3 flex-wrap">
                                                @csrf
                                                <input type="file"
                                                       name="image"
                                                       accept=".jpg,.jpeg,.png,.webp"
                                                       class="text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3
                                                              file:rounded-lg file:border-0 file:text-sm file:font-medium
                                                              file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200"
                                                       required>
                                                <button type="submit"
                                                        class="bg-amber-500 hover:bg-amber-600 text-white text-sm
                                                               px-4 py-1.5 rounded-lg font-medium transition">
                                                    Upload Gambar
                                                </button>
                                            </form>
                                            <p class="text-xs text-amber-600 mt-2">
                                                Format: JPG, PNG, WebP · Maks. 5MB
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-12" id="empty-state">
                        <div class="mx-auto w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mb-4">
                            @if(in_array($questionSet->status, ['pending', 'processing']))
                                <svg class="w-8 h-8 text-amber-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                            @else
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M9 11h6M9 15h6"/>
                                    <path d="M7 3h10a2 2 0 0 1 2 2v16l-4-2-3 2-3-2-4 2V5a2 2 0 0 1 2-2z"/>
                                </svg>
                            @endif
                        </div>

                        <h3 class="text-lg font-bold text-slate-900 mb-2">
                            @if(in_array($questionSet->status, ['pending', 'processing']))
                                AI sedang membuat soal...
                            @else
                                Belum ada pertanyaan
                            @endif
                        </h3>

                        <p class="text-slate-500 max-w-md mx-auto">
                            @if(in_array($questionSet->status, ['pending', 'processing']))
                                Soal sedang digenerate oleh AI di background. Halaman akan otomatis diperbarui.
                            @else
                                Soal belum berhasil digenerate.
                            @endif
                        </p>
                    </div>
                @endforelse
            </div>

            @if($questionSet->source_reference)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mt-6">
                    <h2 class="text-lg font-bold text-slate-900 mb-3">Daftar Pustaka</h2>
                    <p class="text-slate-700 text-sm leading-relaxed" style="padding-left:1.25rem; text-indent:-1.25rem;">
                        {{ $questionSet->source_reference }}.
                    </p>
                </div>
            @endif

        </div>
    </div>

{{-- Polling JS: cek status setiap 3 detik jika masih pending/processing --}}
@if(in_array($questionSet->status, ['pending', 'processing']))
<script>
(function () {
    const statusUrl = "{{ route('bank-soal.status', $questionSet->id) }}";
    let interval;

    function poll() {
        fetch(statusUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'completed' || data.status === 'failed') {
                clearInterval(interval);
                window.location.reload();
            }
        })
        .catch(() => {
            // Abaikan error jaringan, coba lagi di interval berikutnya
        });
    }

    // Mulai polling setelah 3 detik, lanjut setiap 3 detik
    interval = setInterval(poll, 3000);
})();
</script>
@endif

</x-app-layout>