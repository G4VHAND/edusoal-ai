<x-app-layout>
    <div class="p-6 max-w-5xl mx-auto">

        <a href="{{ route('admin.bank-soal.index') }}"
           class="text-blue-600 text-sm mb-4 inline-block">← Kembali ke Bank Soal Sekolah</a>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ $questionSet->title }}</h1>
                    <p class="text-slate-500 mt-1">
                        {{ $questionSet->subject }} · {{ $questionSet->grade }} · {{ $questionSet->topic }}
                    </p>
                    <p class="text-slate-400 text-sm mt-1">
                        Dibuat oleh: <span class="font-medium text-slate-600">{{ $questionSet->user?->name }}</span>
                        · {{ $questionSet->created_at->format('d M Y') }}
                    </p>
                    @if($questionSet->source_reference)
                        <p class="text-slate-400 text-xs mt-2">
                            <span class="font-semibold text-slate-500">📚 Referensi:</span>
                            {{ $questionSet->source_reference }}
                            <a href="https://www.google.com/search?q={{ urlencode($questionSet->source_reference) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="text-blue-600 hover:underline ml-1">(Google)</a>
                            <a href="https://scholar.google.com/scholar?q={{ urlencode($questionSet->source_reference) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="text-blue-600 hover:underline ml-1">(Scholar)</a>
                            <a href="https://www.youtube.com/results?search_query={{ urlencode($questionSet->source_reference) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="text-blue-600 hover:underline ml-1">(YouTube)</a>
                        </p>
                    @endif
                </div>
                <div class="flex gap-2">
                    <span class="px-3 py-1 rounded-full text-sm
                        {{ $questionSet->question_type === 'multiple_choice' ? 'bg-blue-100 text-blue-700' : 'bg-violet-100 text-violet-700' }}">
                        {{ $questionSet->question_type === 'multiple_choice' ? 'Pilihan Ganda' : 'Essay' }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-sm
                        {{ $questionSet->difficulty === 'mudah' ? 'bg-green-100 text-green-700' : ($questionSet->difficulty === 'sedang' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                        {{ ucfirst($questionSet->difficulty) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Daftar Soal (read-only) --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="font-bold text-slate-900 mb-4">
                Daftar Soal ({{ $questionSet->questions->count() }} soal)
            </h2>

            @forelse($questionSet->questions as $index => $question)
            <div class="border border-slate-200 rounded-xl p-5 mb-4">
                @if($question->image_path)
                    @php
                        $imgPath = storage_path('app/private/' . $question->image_path);
                        if (!file_exists($imgPath)) {
                            $imgPath = storage_path('app/' . $question->image_path);
                        }
                        $imgData = file_exists($imgPath) ? base64_encode(file_get_contents($imgPath)) : null;
                        $imgMime = file_exists($imgPath) ? mime_content_type($imgPath) : 'image/jpeg';
                    @endphp
                    @if($imgData)
                        <img src="data:{{ $imgMime }};base64,{{ $imgData }}"
                             class="max-h-48 rounded-xl border border-slate-200 mb-3 object-contain">
                    @endif
                @endif

                <p class="text-slate-900 mb-3 text-justify">
                    <strong>{{ $index + 1 }}.</strong> {!! \App\Services\Document\TextFormatter::toHtml($question->question_text) !!}
                </p>

                @if($questionSet->question_type === 'multiple_choice')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-slate-700">
                        <div class="bg-slate-50 rounded-lg p-3">A. {!! \App\Services\Document\TextFormatter::toHtml($question->option_a) !!}</div>
                        <div class="bg-slate-50 rounded-lg p-3">B. {!! \App\Services\Document\TextFormatter::toHtml($question->option_b) !!}</div>
                        <div class="bg-slate-50 rounded-lg p-3">C. {!! \App\Services\Document\TextFormatter::toHtml($question->option_c) !!}</div>
                        <div class="bg-slate-50 rounded-lg p-3">D. {!! \App\Services\Document\TextFormatter::toHtml($question->option_d) !!}</div>
                    </div>
                @endif

                <div class="mt-3 bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-justify">
                    <span class="font-semibold text-green-700">Jawaban:</span>
                    <span class="text-green-700">{!! \App\Services\Document\TextFormatter::toHtml($question->correct_answer) !!}</span>
                </div>

                @if($question->explanation)
                    <div class="mt-2 bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-700 text-justify">
                        <span class="font-semibold">Pembahasan:</span> {!! \App\Services\Document\TextFormatter::toHtml($question->explanation) !!}
                    </div>
                @endif
            </div>
            @empty
            <p class="text-slate-400 text-center py-8">Belum ada soal.</p>
            @endforelse
        </div>

    </div>
</x-app-layout>