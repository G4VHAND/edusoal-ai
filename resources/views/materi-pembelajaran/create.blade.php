@php
    $currentUser = auth()->user();
@endphp

<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full max-w-[640px] mx-auto px-5 lg:px-8 py-8">

            <div class="mb-6">
                <a href="{{ route('materi-pembelajaran.index') }}" class="text-xs text-slate-500 hover:text-blue-600 flex items-center gap-1 mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Kembali
                </a>
                <h1 class="text-2xl font-bold text-slate-900 mb-1">Unggah Materi</h1>
                <p class="text-sm text-slate-500">Materi ini akan dipakai sebagai referensi AI saat kamu generate soal.</p>
            </div>

            @if($errors->any())
            <div class="bg-rose-50 text-rose-700 text-sm px-4 py-3 rounded-xl mb-6">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('materi-pembelajaran.store') }}" enctype="multipart/form-data"
                  class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-6 space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Judul Materi</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           placeholder="Contoh: Rangkuman Bab Fotosintesis"
                           class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Mata Pelajaran <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="subject" value="{{ old('subject') }}"
                           placeholder="Contoh: Biologi"
                           class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Deskripsi <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea name="description" rows="3"
                              placeholder="Ringkasan singkat isi materi..."
                              class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
                </div>

                @if($currentUser->isSchoolAdmin())
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Bagikan ke</label>
                    <div class="flex gap-3">
                        <label class="flex-1 flex items-center gap-2 border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="visibility" value="pribadi" checked class="text-blue-600">
                            Pribadi (cuma saya)
                        </label>
                        <label class="flex-1 flex items-center gap-2 border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="visibility" value="sekolah" class="text-blue-600">
                            Sekolah (semua guru)
                        </label>
                    </div>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">File</label>
                    <input type="file" name="file" required accept=".pdf,.doc,.docx,.txt"
                           class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700 file:text-xs file:font-semibold">
                    <p class="text-[11px] text-slate-400 mt-1.5">Format PDF, DOC, DOCX, atau TXT. Maksimal 10MB.</p>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('materi-pembelajaran.index') }}"
                       class="px-4 py-2.5 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-100">
                        Batal
                    </a>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold">
                        Unggah Materi
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
