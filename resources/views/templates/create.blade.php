<x-app-layout>
    <div class="p-6 max-w-xl mx-auto">
        <a href="{{ route('templates.index') }}"
           class="text-blue-600 text-sm mb-4 inline-block">← Kembali ke Template Dokumen</a>

        <h1 class="text-2xl font-bold text-slate-900 mb-6">Upload Template Word</h1>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('templates.store') }}"
              enctype="multipart/form-data"
              class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Template</label>
                <input type="text" name="name" value="{{ old('name') }}"
                       placeholder="Contoh: Format UTS SMPN 1"
                       class="w-full border border-slate-300 rounded-xl p-3" required>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Untuk Versi</label>
                <select name="type" class="w-full border border-slate-300 rounded-xl p-3">
                    <option value="guru">Guru (dengan kunci jawaban)</option>
                    <option value="siswa">Siswa (tanpa kunci jawaban)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">File Template (.docx)</label>
                <input type="file" name="template" accept=".docx"
                       class="w-full text-sm text-slate-600 border border-slate-300 rounded-xl p-3" required>
                <p class="text-xs text-slate-400 mt-1">Maksimal 5MB, format .docx</p>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_default" id="is_default" value="1"
                       class="w-4 h-4 rounded border-slate-300 text-blue-600">
                <label for="is_default" class="text-sm text-slate-600">
                    Jadikan template default untuk tipe ini
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700">
                    Upload Template
                </button>
                <a href="{{ route('templates.index') }}"
                   class="px-6 py-3 rounded-xl border border-slate-300 text-slate-700">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-app-layout>