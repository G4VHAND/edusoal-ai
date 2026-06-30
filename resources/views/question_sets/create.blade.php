<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="max-w-6xl mx-auto px-6 py-8">

            <div class="mb-8">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-2 text-blue-600 font-semibold mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                    Kembali ke Dashboard
                </a>

                <h1 class="text-3xl font-bold text-slate-900">
                    Generate Soal
                </h1>

                <p class="text-slate-500 mt-2">
                    Buat bank soal baru berdasarkan mata pelajaran, kelas, topik, jenis soal, dan tingkat kesulitan.
                </p>
            </div>

            {{-- Error quota — ditampilkan paling atas dan mencolok --}}
            @if($errors->has('quota'))
                <div class="mb-6 bg-red-50 border-2 border-red-300 rounded-2xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-red-800 text-lg">Quota Generate Soal Habis</h3>
                            <p class="text-red-700 mt-1">{{ $errors->first('quota') }}</p>
                            <div class="mt-3 flex gap-3">
                                <a href="{{ route('dashboard') }}"
                                   class="text-sm bg-red-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-red-700 transition">
                                    Lihat Dashboard
                                </a>
                                <a href="{{ route('admin.dashboard') }}"
                                   class="text-sm border border-red-300 text-red-700 px-4 py-2 rounded-xl font-medium hover:bg-red-50 transition">
                                    Upgrade Plan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Error rate limiting --}}
            @if($errors->has('rate_limit'))
                <div class="mb-6 bg-amber-50 border border-amber-300 rounded-2xl p-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        <p class="text-amber-800 font-medium">{{ $errors->first('rate_limit') }}</p>
                    </div>
                </div>
            @endif

            {{-- Error lain --}}
            @if($errors->any() && !$errors->has('quota') && !$errors->has('rate_limit'))
                <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                    <p class="text-red-700 text-sm font-medium">{{ $errors->first() }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <form method="POST" action="{{ route('generate-soal.store') }}" enctype="multipart/form-data" onsubmit="showGenerateLoading()">
                        @csrf

                        <div class="mb-5">
                            <label class="block font-semibold text-slate-700 mb-2">
                                Judul Bank Soal
                            </label>

                            <input
                                name="title"
                                type="text"
                                class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Contoh: UTS Matematika Kelas 12"
                                value="{{ old('title') }}">

                            @error('title')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-2">
                                    Mata Pelajaran
                                </label>

                                <input
                                    name="subject"
                                    type="text"
                                    class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Contoh: IPA"
                                    value="{{ old('subject') }}">

                                @error('subject')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-2">
                                    Kelas
                                </label>

                                <select
                                    name="grade"
                                    class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">-- Pilih Kelas --</option>
                                    <optgroup label="SD">
                                        @foreach(['Kelas 1 SD','Kelas 2 SD','Kelas 3 SD','Kelas 4 SD','Kelas 5 SD','Kelas 6 SD'] as $k)
                                            <option value="{{ $k }}" {{ old('grade') == $k ? 'selected' : '' }}>{{ $k }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="SMP">
                                        @foreach(['Kelas 7 SMP','Kelas 8 SMP','Kelas 9 SMP'] as $k)
                                            <option value="{{ $k }}" {{ old('grade') == $k ? 'selected' : '' }}>{{ $k }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="SMA">
                                        @foreach(['Kelas 10 SMA','Kelas 11 SMA','Kelas 12 SMA'] as $k)
                                            <option value="{{ $k }}" {{ old('grade') == $k ? 'selected' : '' }}>{{ $k }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="SMK">
                                        @foreach(['Kelas 10 SMK','Kelas 11 SMK','Kelas 12 SMK'] as $k)
                                            <option value="{{ $k }}" {{ old('grade') == $k ? 'selected' : '' }}>{{ $k }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>

                                @error('grade')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-5">
                            <label class="block font-semibold text-slate-700 mb-2">
                                Topik
                            </label>

                            <input
                                name="topic"
                                type="text"
                                class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Contoh: Sistem Pencernaan"
                                value="{{ old('topic') }}">

                            @error('topic')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-5">
                            <label class="block font-semibold text-slate-700 mb-2">
                                Upload Materi Pembelajaran
                                <span class="text-slate-400 font-normal">(Opsional)</span>
                            </label>

                            <div class="border-2 border-dashed border-slate-300 rounded-2xl p-5 bg-slate-50">
                                <input
                                    name="material_file"
                                    type="file"
                                    accept=".pdf,.doc,.docx,.txt"
                                    class="w-full text-slate-600">

                                <p class="text-sm text-slate-500 mt-3">
                                    File yang didukung: PDF, DOC, DOCX, atau TXT. AI akan membuat soal berdasarkan isi materi ini.
                                </p>
                            </div>

                            @error('material_file')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Upload gambar untuk soal berbasis visual --}}
                        <div class="mt-4">
                            <label class="block font-semibold text-slate-700 mb-2">
                                Upload Gambar / Diagram
                                <span class="text-slate-400 font-normal">(Opsional — khusus Gemini)</span>
                            </label>

                            <div class="border-2 border-dashed border-blue-200 rounded-2xl p-5 bg-blue-50">
                                <input
                                    name="material_image"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.gif,.webp"
                                    class="w-full text-slate-600"
                                    id="imageInput">

                                <p class="text-sm text-slate-500 mt-3">
                                    📷 Untuk soal berbasis gambar: diagram sel, peta, grafik, bangun ruang, dll.<br>
                                    AI (Gemini Vision) akan mendeskripsikan gambar dan membuat soal berdasarkan isinya.<br>
                                    <span class="text-amber-600 font-medium">Fitur gambar hanya tersedia dengan provider Gemini.</span>
                                </p>

                                {{-- Preview gambar --}}
                                <div id="imagePreview" class="hidden mt-3">
                                    <img id="previewImg" src="" alt="Preview" class="max-h-40 rounded-xl border border-blue-200">
                                </div>
                            </div>

                            @error('material_image')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-5">

                            <div>
                                <label class="block font-semibold text-slate-700 mb-2">
                                    Jenis Soal
                                </label>

                                <select
                                    name="question_type"
                                    class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="multiple_choice" {{ old('question_type') == 'multiple_choice' ? 'selected' : '' }}>
                                        Pilihan Ganda
                                    </option>
                                    <option value="essay" {{ old('question_type') == 'essay' ? 'selected' : '' }}>
                                        Essay
                                    </option>
                                </select>

                                @error('question_type')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-2">
                                    Tingkat Kesulitan
                                    <span class="text-xs font-normal text-slate-400 ml-1">(Taksonomi Bloom)</span>
                                </label>

                                <select
                                    name="difficulty"
                                    class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="mudah" {{ old('difficulty') == 'mudah' ? 'selected' : '' }}>
                                        Mudah — C1/C2 · Mengingat & Memahami
                                    </option>
                                    <option value="sedang" {{ old('difficulty', 'sedang') == 'sedang' ? 'selected' : '' }}>
                                        Sedang — C3/C4 · Mengaplikasikan & Menganalisis
                                    </option>
                                    <option value="sulit" {{ old('difficulty') == 'sulit' ? 'selected' : '' }}>
                                        Sulit — C5/C6 · Mengevaluasi & Mencipta
                                    </option>
                                </select>

                                <p class="text-xs text-slate-400 mt-1">
                                    Kesulitan disesuaikan otomatis dengan jenjang kelas yang dipilih.
                                </p>

                                @error('difficulty')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-2">
                                    Kurikulum
                                </label>

                                <select
                                    name="curriculum"
                                    class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="merdeka" {{ old('curriculum', 'merdeka') == 'merdeka' ? 'selected' : '' }}>
                                        Kurikulum Merdeka
                                    </option>
                                    <option value="k13" {{ old('curriculum') == 'k13' ? 'selected' : '' }}>
                                        Kurikulum 2013 (K13)
                                    </option>
                                </select>

                                @error('curriculum')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-2">
                                    Jenis Asesmen
                                </label>

                                <select
                                    name="assessment_type"
                                    class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="reguler" {{ old('assessment_type', 'reguler') == 'reguler' ? 'selected' : '' }}>
                                        Reguler
                                    </option>
                                    <option value="hots" {{ old('assessment_type') == 'hots' ? 'selected' : '' }}>
                                        HOTS (Higher Order Thinking Skills)
                                    </option>
                                    <option value="akm" {{ old('assessment_type') == 'akm' ? 'selected' : '' }}>
                                        AKM (Asesmen Kompetensi Minimum)
                                    </option>
                                </select>

                                <p class="text-xs text-slate-400 mt-1">
                                    HOTS & AKM menambahkan stimulus/wacana pada soal.
                                </p>

                                @error('assessment_type')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-2">
                                    Jumlah Soal
                                </label>

                                <input
                                    name="total_questions"
                                    type="number"
                                    min="1"
                                    max="50"
                                    class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    value="{{ old('total_questions', 10) }}">

                                @error('total_questions')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Provider dipilih otomatis oleh sistem --}}
                            <input type="hidden" name="ai_provider" value="gemini">
                        </div>

                        <div class="flex flex-wrap gap-3 mt-8">
                            <button
                                id="generateButton"
                                type="submit"
                                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-sm disabled:opacity-70 disabled:cursor-not-allowed">
                                <svg id="generateIcon" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                <span id="generateText">Generate Soal</span>
                            </button>

                            <a href="{{ route('bank-soal') }}"
                               class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 px-6 py-3 rounded-xl font-semibold">
                                Lihat Bank Soal
                            </a>
                        </div>
                            <p id="generateInfo" class="text-sm text-slate-500 mt-3 hidden">
                                AI sedang membuat soal. Proses dapat memakan waktu 10–60 detik.
                            </p>
                    </form>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 h-fit">

                    {{-- Quota indicator --}}
                    @php
                        $user      = auth()->user();
                        $plan      = $user->subscriptionPlan;
                        $limit     = $plan ? $plan->quota_per_month : 10;
                        $used      = $user->quota_used_this_month;
                        $remaining = $user->remainingQuota();
                        $isUnlimited = ($limit === -1);
                        $pct       = (!$isUnlimited && $limit > 0) ? min(100, round(($used / $limit) * 100)) : 0;
                    @endphp

                    <div class="mb-5 pb-5 border-b border-slate-100">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-slate-700">Quota Bulan Ini</p>
                            <span class="text-sm font-bold {{ $remaining === -1 ? 'text-green-600' : ($remaining <= 2 ? 'text-red-600' : 'text-blue-600') }}">
                                {{ $remaining === -1 ? '∞' : $remaining }} sisa
                            </span>
                        </div>

                        @if(!$isUnlimited)
                            <div class="w-full bg-slate-100 rounded-full h-2 mb-2">
                                <div class="h-2 rounded-full transition-all {{ $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-blue-500') }}"
                                     style="width: {{ $pct }}%"></div>
                            </div>
                            <p class="text-xs text-slate-400">
                                {{ $used }} dari {{ $limit }} generate dipakai · Paket {{ $plan?->name ?? 'Free' }}
                            </p>
                        @else
                            <p class="text-xs text-slate-400">Unlimited · Paket {{ $plan?->name }}</p>
                        @endif

                        @if($remaining === 0)
                            <div class="mt-3 bg-red-50 border border-red-200 rounded-xl p-3 text-xs text-red-700">
                                <p class="font-semibold">Quota habis!</p>
                                <p class="mt-0.5">Quota akan reset pada awal bulan depan, atau upgrade paket untuk generate lebih banyak.</p>
                            </div>
                        @elseif(!$isUnlimited && $remaining <= 2)
                            <div class="mt-3 bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-700">
                                <p class="font-semibold">⚠️ Quota hampir habis!</p>
                                <p class="mt-0.5">Sisa {{ $remaining }} generate lagi bulan ini.</p>
                            </div>
                        @endif
                    </div>

                    <div class="bg-blue-100 text-blue-600 w-12 h-12 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M9 11h6M9 15h6"/>
                            <path d="M7 3h10a2 2 0 0 1 2 2v16l-4-2-3 2-3-2-4 2V5a2 2 0 0 1 2-2z"/>
                        </svg>
                    </div>

                    <h2 class="text-lg font-bold text-slate-900 mb-2">
                        Panduan Generate
                    </h2>

                    <p class="text-slate-500 text-sm mb-4">
                        Isi informasi soal secara lengkap agar hasil bank soal lebih sesuai kebutuhan pembelajaran.
                    </p>

                    <div class="space-y-3 text-sm text-slate-600">
                        <div class="flex gap-2">
                            <span class="font-bold text-blue-600">1.</span>
                            <p>Tentukan mata pelajaran dan kelas.</p>
                        </div>

                        <div class="flex gap-2">
                            <span class="font-bold text-blue-600">2.</span>
                            <p>Masukkan topik pembelajaran secara spesifik.</p>
                        </div>

                        <div class="flex gap-2">
                            <span class="font-bold text-blue-600">3.</span>
                            <p>Pilih jenis soal dan tingkat kesulitan.</p>
                        </div>

                        <div class="flex gap-2">
                            <span class="font-bold text-blue-600">4.</span>
                            <p>Data akan tersimpan ke Bank Soal.</p>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <script>
        function showGenerateLoading() {
            const button = document.getElementById('generateButton');
            const info   = document.getElementById('generateInfo');

            button.disabled = true;
            button.innerHTML = `
                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v8z">
                    </path>
                </svg>
                Sedang Generate...
            `;

            if (info) {
                info.classList.remove('hidden');
            }

            return true;
        }

        // Preview gambar sebelum upload
        document.getElementById('imageInput').addEventListener('change', function (e) {
            const file    = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            const img     = document.getElementById('previewImg');

            if (file) {
                const reader = new FileReader();
                reader.onload = function (ev) {
                    img.src = ev.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('hidden');
            }
        });
        document.querySelector('select[name="ai_provider"]')?.addEventListener('change', function () {
            const imageInput = document.getElementById('imageInput');
            if (imageInput.files.length > 0 && this.value !== 'gemini') {
                alert('⚠️ Anda memiliki gambar yang diunggah, tetapi provider ' + this.value + ' tidak mendukung gambar. Fitur gambar hanya tersedia di Gemini.');
            }
        });
    </script>
</x-app-layout>