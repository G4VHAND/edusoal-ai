<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="max-w-5xl mx-auto px-6 py-8">

            <div class="mb-8">
                <a href="{{ route('bank-soal.show', $questionSet->id) }}"
                   class="inline-flex items-center gap-2 text-blue-600 font-semibold mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                    Kembali ke Detail Bank Soal
                </a>

                <h1 class="text-3xl font-bold text-slate-900">
                    Edit Bank Soal
                </h1>

                <p class="text-slate-500 mt-2">
                    Perbarui informasi bank soal seperti judul, mata pelajaran, kelas, topik, jenis soal, dan tingkat kesulitan.
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">

                <form method="POST" action="{{ route('bank-soal.update', $questionSet->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-5">
                        <label class="block font-semibold text-slate-700 mb-2">
                            Judul Bank Soal
                        </label>

                        <input
                            name="title"
                            type="text"
                            class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            value="{{ old('title', $questionSet->title) }}">

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
                                value="{{ old('subject', $questionSet->subject) }}">

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
                                        <option value="{{ $k }}" {{ old('grade', $questionSet->grade) == $k ? 'selected' : '' }}>{{ $k }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="SMP">
                                    @foreach(['Kelas 7 SMP','Kelas 8 SMP','Kelas 9 SMP'] as $k)
                                        <option value="{{ $k }}" {{ old('grade', $questionSet->grade) == $k ? 'selected' : '' }}>{{ $k }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="SMA">
                                    @foreach(['Kelas 10 SMA','Kelas 11 SMA','Kelas 12 SMA'] as $k)
                                        <option value="{{ $k }}" {{ old('grade', $questionSet->grade) == $k ? 'selected' : '' }}>{{ $k }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="SMK">
                                    @foreach(['Kelas 10 SMK','Kelas 11 SMK','Kelas 12 SMK'] as $k)
                                        <option value="{{ $k }}" {{ old('grade', $questionSet->grade) == $k ? 'selected' : '' }}>{{ $k }}</option>
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
                            value="{{ old('topic', $questionSet->topic) }}">

                        @error('topic')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">

                        <div>
                            <label class="block font-semibold text-slate-700 mb-2">
                                Jenis Soal
                            </label>

                            <select
                                name="question_type"
                                class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="multiple_choice" {{ old('question_type', $questionSet->question_type) == 'multiple_choice' ? 'selected' : '' }}>
                                    Pilihan Ganda
                                </option>
                                <option value="essay" {{ old('question_type', $questionSet->question_type) == 'essay' ? 'selected' : '' }}>
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
                                <option value="mudah" {{ old('difficulty', $questionSet->difficulty) == 'mudah' ? 'selected' : '' }}>
                                    Mudah — C1/C2 · Mengingat & Memahami
                                </option>
                                <option value="sedang" {{ old('difficulty', $questionSet->difficulty) == 'sedang' ? 'selected' : '' }}>
                                    Sedang — C3/C4 · Mengaplikasikan & Menganalisis
                                </option>
                                <option value="sulit" {{ old('difficulty', $questionSet->difficulty) == 'sulit' ? 'selected' : '' }}>
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
                                Jumlah Soal
                            </label>

                            <input
                                name="total_questions"
                                type="number"
                                min="1"
                                max="50"
                                class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                value="{{ old('total_questions', $questionSet->total_questions) }}">

                            @error('total_questions')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    <div class="flex flex-wrap gap-3 mt-8">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-sm">
                            Simpan Perubahan
                        </button>

                        <a href="{{ route('bank-soal.show', $questionSet->id) }}"
                           class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 px-6 py-3 rounded-xl font-semibold">
                            Batal
                        </a>
                    </div>
                </form>

            </div>

        </div>
    </div>
</x-app-layout>