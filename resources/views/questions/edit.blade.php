<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full px-6 lg:px-8 py-8 max-w-3xl mx-auto">

            <a href="{{ route('bank-soal.show', $question->question_set_id) }}"
               class="inline-flex items-center gap-1.5 text-slate-500 hover:text-slate-700 text-sm font-medium mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
                Kembali ke {{ $question->questionSet->title }}
            </a>

            <div class="flex items-start gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Edit Soal</h1>
                    <p class="text-slate-500 text-sm">
                        Perbaiki langsung teks soal, jawaban, atau pembahasan kalau hasil AI kurang akurat.
                        Perubahan ini tidak memanggil AI lagi.
                    </p>
                </div>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                    <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('questions.update', $question->id) }}"
                  class="bg-white rounded-2xl border border-slate-200 p-6 space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-semibold text-slate-700 mb-2">Teks Soal</label>
                    <textarea name="question_text" rows="4" required
                        class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('question_text', $question->question_text) }}</textarea>
                    <p class="text-xs text-slate-400 mt-1">
                        Tips: tandai kata penting dengan **begini** untuk membuatnya tebal saat export.
                    </p>
                </div>

                @if($question->questionSet->question_type === 'multiple_choice')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-2">Opsi A</label>
                            <input type="text" name="option_a" required
                                value="{{ old('option_a', $question->option_a) }}"
                                class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-2">Opsi B</label>
                            <input type="text" name="option_b" required
                                value="{{ old('option_b', $question->option_b) }}"
                                class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-2">Opsi C</label>
                            <input type="text" name="option_c" required
                                value="{{ old('option_c', $question->option_c) }}"
                                class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-2">Opsi D</label>
                            <input type="text" name="option_d" required
                                value="{{ old('option_d', $question->option_d) }}"
                                class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-2">Jawaban Benar</label>
                        <select name="correct_answer" required
                            class="w-full sm:w-40 border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @foreach(['A', 'B', 'C', 'D'] as $letter)
                                <option value="{{ $letter }}" {{ old('correct_answer', $question->correct_answer) === $letter ? 'selected' : '' }}>
                                    {{ $letter }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div>
                        <label class="block font-semibold text-slate-700 mb-2">Jawaban / Kunci Jawaban</label>
                        <textarea name="correct_answer" rows="3" required
                            class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('correct_answer', $question->correct_answer) }}</textarea>
                    </div>
                @endif

                <div>
                    <label class="block font-semibold text-slate-700 mb-2">Pembahasan</label>
                    <textarea name="explanation" rows="4"
                        class="w-full border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('explanation', $question->explanation) }}</textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition">
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('bank-soal.show', $question->question_set_id) }}"
                       class="px-6 py-3 rounded-xl font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Batal
                    </a>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
