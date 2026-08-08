<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full max-w-[640px] mx-auto px-5 lg:px-8 py-8">

            <div class="mb-6">
                <a href="{{ route('admin.teachers.index') }}" class="text-xs text-slate-500 hover:text-blue-600 flex items-center gap-1 mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Kembali ke Manajemen Guru
                </a>
                <h1 class="text-2xl font-bold text-slate-900 mb-1">Import Guru</h1>
                <p class="text-sm text-slate-500">Tambah banyak guru sekaligus lewat file CSV, tanpa isi form satu-satu.</p>
            </div>

            @if($errors->any())
            <div class="bg-rose-50 text-rose-700 text-sm px-4 py-3 rounded-xl mb-6">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <div class="bg-blue-50 rounded-2xl p-5 mb-6">
                <h2 class="text-sm font-bold text-blue-900 mb-2">Format CSV</h2>
                <p class="text-xs text-blue-700 mb-3">
                    File CSV harus punya kolom <code class="bg-white px-1.5 py-0.5 rounded">name</code> dan
                    <code class="bg-white px-1.5 py-0.5 rounded">email</code>. Password akan dibuatkan otomatis
                    dan ditampilkan setelah import selesai — belum ada email undangan otomatis, jadi catat/salin
                    passwordnya untuk dibagikan manual ke masing-masing guru.
                </p>
                <a href="{{ route('admin.teachers.import.template') }}"
                   class="inline-flex items-center gap-1.5 bg-white text-blue-700 text-xs font-semibold px-3 py-2 rounded-lg hover:bg-blue-100 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                    Unduh Template CSV
                </a>
            </div>

            <form method="POST" action="{{ route('admin.teachers.import.store') }}" enctype="multipart/form-data"
                  class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-6 space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">File CSV</label>
                    <input type="file" name="file" required accept=".csv,.txt"
                           class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700 file:text-xs file:font-semibold">
                    <p class="text-[11px] text-slate-400 mt-1.5">Maksimal 2MB.</p>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('admin.teachers.index') }}"
                       class="px-4 py-2.5 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-100">
                        Batal
                    </a>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold">
                        Import Guru
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
