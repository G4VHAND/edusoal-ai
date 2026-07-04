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

                            {{-- Edit --}}
                            <a href="{{ route('bank-soal.edit', $questionSet->id) }}"
                                class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-5 py-3 rounded-xl font-semibold shadow-sm">

                                    <svg class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path d="M12 20h9"/>
                                        <path d="M16.5 3.5a2.1 2.1 0 113 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                    </svg>

                                    Edit
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

                                    <a href="{{ route('bank-soal.export-student-word', $questionSet->id) }}"
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
                                            <p class="font-medium">Word Soal Siswa</p>
                                            <p class="text-xs text-slate-500">Versi siswa</p>
                                        </div>
                                    </a>

                                    <div class="border-t border-slate-100 my-1"></div>

                                    <a href="{{ route('bank-soal.export-template', ['questionSet' => $questionSet->id, 'type' => 'guru']) }}"
                                    class="flex items-center gap-3 px-4 py-3 text-slate-700 hover:bg-slate-100">
                                        <svg class="w-5 h-5 text-violet-600"
                                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                            <path d="M8 7h8M8 11h8M8 15h5"/>
                                        </svg>
                                        <div>
                                            <p class="font-medium">Template Sekolah (Guru)</p>
                                            <p class="text-xs text-slate-500">Pakai kop surat & format sendiri</p>
                                        </div>
                                    </a>

                                    <a href="{{ route('bank-soal.export-template', ['questionSet' => $questionSet->id, 'type' => 'siswa']) }}"
                                    class="flex items-center gap-3 px-4 py-3 text-slate-700 hover:bg-slate-100">
                                        <svg class="w-5 h-5 text-violet-600"
                                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                            <path d="M8 7h8M8 11h8M8 15h5"/>
                                        </svg>
                                        <div>
                                            <p class="font-medium">Template Sekolah (Siswa)</p>
                                            <p class="text-xs text-slate-500">Pakai kop surat & format sendiri</p>
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

                @if(in_array($questionSet->status, ['pending', 'processing']))
                {{-- STATUS: Sedang diproses — tampilkan loading, polling setiap 3 detik --}}
                <div id="processing-banner"
                     class="bg-amber-50 border border-amber-200 rounded-xl p-4 mt-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-amber-600 animate-spin" fill="none" viewBox="0 0 24 24">
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
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mt-4">
                    <p class="font-semibold text-red-800">AI gagal memproses soal</p>
                    <p class="text-sm text-red-600 mt-1">{{ $questionSet->ai_error }}</p>
                </div>

                @elseif($questionSet->is_ai_generated)
                {{-- STATUS: Selesai --}}
                <div class="bg-violet-50 border border-violet-200 rounded-xl p-4 mt-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-violet-800">✅ AI Generated</p>
                            @if($questionSet->material_image)
                                <p class="text-sm text-blue-600 mt-1">📷 Dibuat dari gambar + Vision AI</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Disclaimer wajib review guru --}}
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mt-3">
                    <p class="text-amber-800 text-sm">
                        <span class="font-semibold">⚠️ Perlu direview:</span>
                        Soal ini digenerate oleh AI dan mungkin mengandung ketidakakuratan.
                        Setiap soal menyertakan kolom <strong>Sumber</strong> yang menunjukkan dasar pembuatan soal.
                        Harap periksa setiap soal sebelum digunakan dalam ujian.
                    </p>
                </div>
                @endif

                @if($questionSet->material_file)

                <div class="bg-white rounded-2xl border border-slate-200 p-5 mt-6">

                    <h3 class="font-bold text-slate-900 mb-3">
                        Materi Pembelajaran
                    </h3>

                    <p class="text-slate-600 mb-4">
                        File materi yang digunakan untuk generate soal.
                    </p>

                    <div class="flex gap-3">
                        {{-- File materi disimpan di disk private, tidak bisa diakses langsung via URL --}}
                        <p class="text-slate-500 text-sm">
                            📎 {{ $questionSet->material_original_name ?? basename($questionSet->material_file) }}
                        </p>
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
                            <p class="font-bold text-slate-900">
                                {{ $index + 1 }}. {{ $question->question_text }}
                            </p>

                            <form method="POST"
                                  action="{{ route('bank-soal.question.destroy', [$questionSet->id, $question->id]) }}"
                                  class="shrink-0">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Hapus soal nomor {{ $index + 1 }} ini? Tidak bisa dikembalikan.')"
                                        title="Hapus soal ini"
                                        class="text-slate-300 hover:text-red-500 transition text-lg leading-none px-1">
                                    &times;
                                </button>
                            </form>
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
                                    A. {{ $question->option_a }}
                                </div>
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                    B. {{ $question->option_b }}
                                </div>
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                    C. {{ $question->option_c }}
                                </div>
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                    D. {{ $question->option_d }}
                                </div>
                            </div>
                        @endif

                        <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3">
                            <p class="text-green-700 font-semibold">
                                Jawaban: {{ $question->correct_answer }}
                            </p>
                        </div>

                        @if($question->explanation)
                            <div class="mt-3 bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <p class="text-blue-700">
                                    <span class="font-semibold">Pembahasan:</span>
                                    {{ $question->explanation }}
                                </p>
                            </div>
                        @endif

                        @if($question->source_paragraph)
                            <div class="mt-2 bg-slate-50 border border-slate-200 rounded-lg p-3">
                                <p class="text-slate-500 text-xs">
                                    <span class="font-semibold text-slate-600">📎 Sumber:</span>
                                    {{ $question->source_paragraph }}
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