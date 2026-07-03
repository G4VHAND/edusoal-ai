<x-app-layout>
    <div class="p-6 max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Template Dokumen</h1>
                <p class="text-slate-500 text-sm mt-1">
                    Upload format Word sekolah/pribadi Anda agar export soal otomatis sesuai template.
                </p>
            </div>
            <a href="{{ route('templates.create') }}"
               class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-semibold text-sm">
                + Upload Template
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('errors') && session('errors')->has('template'))
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 mb-4">
                {{ session('errors')->first('template') }}
            </div>
        @endif

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-sm text-blue-700">
            <p class="font-semibold mb-1">💡 Cara membuat template</p>
            <p>Buat dokumen Word dengan kop surat sekolah Anda, lalu sisipkan placeholder berikut di tempat yang sesuai:</p>
            <code class="block bg-white rounded-lg p-2 mt-2 text-xs">
                ${judul_soal} ${mata_pelajaran} ${kelas} ${topik} ${tanggal} ${nama_sekolah} ${kepala_sekolah}
            </code>
            <p class="mt-2">Untuk daftar soal otomatis, buat tabel 1 baris berisi: <code class="bg-white px-1 rounded">${nomor}</code>, <code class="bg-white px-1 rounded">${soal}</code>, <code class="bg-white px-1 rounded">${jawaban}</code> — sistem akan menduplikasi baris ini sesuai jumlah soal.</p>
        </div>

        @if($schoolTemplates->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-6">
            <div class="px-5 py-3 bg-violet-50 border-b border-violet-100">
                <p class="font-semibold text-violet-800 text-sm">🏫 Template dari Sekolah</p>
                <p class="text-xs text-violet-600 mt-0.5">
                    Diupload oleh admin sekolah Anda. Dipakai otomatis saat Anda export soal
                    dengan template — Anda tidak perlu upload template sendiri.
                </p>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-slate-600">
                        <th class="px-5 py-3">Nama Template</th>
                        <th class="px-5 py-3">Tipe</th>
                        <th class="px-5 py-3">File</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($schoolTemplates as $template)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $template->name }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ $template->type === 'guru' ? 'bg-blue-100 text-blue-700' : 'bg-violet-100 text-violet-700' }}">
                                {{ ucfirst($template->type) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 text-xs">{{ $template->original_filename }}</td>
                        <td class="px-5 py-3">
                            @if($template->is_default)
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                                    ✓ Dipakai otomatis
                                </span>
                            @else
                                <span class="px-2 py-1 bg-slate-100 text-slate-500 rounded-full text-xs">
                                    Bukan default
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-100">
                <p class="font-semibold text-slate-700 text-sm">
                    {{ auth()->user()->isSchoolAdmin() ? 'Template Sekolah' : 'Template Personal Saya' }}
                </p>
                @if(! auth()->user()->isSchoolAdmin())
                    <p class="text-xs text-slate-500 mt-0.5">
                        Opsional — kalau sekolah Anda belum punya template default, Anda bisa upload sendiri di sini.
                    </p>
                @endif
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-slate-600">
                        <th class="px-5 py-3">Nama Template</th>
                        <th class="px-5 py-3">Tipe</th>
                        <th class="px-5 py-3">File</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($templates as $template)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $template->name }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ $template->type === 'guru' ? 'bg-blue-100 text-blue-700' : 'bg-violet-100 text-violet-700' }}">
                                {{ ucfirst($template->type) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 text-xs">{{ $template->original_filename }}</td>
                        <td class="px-5 py-3">
                            @if($template->is_default)
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                                    ✓ Default
                                </span>
                            @else
                                <form method="POST" action="{{ route('templates.set-default', $template) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs text-blue-600 hover:underline">
                                        Set sebagai default
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <form method="POST" action="{{ route('templates.destroy', $template) }}">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Hapus template {{ $template->name }}?')"
                                        class="text-xs text-red-600 hover:underline">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                            @if(auth()->user()->isSchoolAdmin() || $schoolTemplates->isEmpty())
                                Belum ada template diupload. Export soal akan menggunakan format standar.
                            @else
                                Anda belum upload template personal — tidak masalah, export akan
                                otomatis memakai template sekolah di atas.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>