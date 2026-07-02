<x-app-layout>
    <div class="p-6 max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Kop Surat Sekolah</h1>
        <p class="text-slate-500 mb-6">
            Informasi ini akan otomatis muncul di setiap export PDF dan Word soal yang dibuat guru di sekolah Anda.
        </p>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.letterhead.update') }}"
              enctype="multipart/form-data"
              class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
            @csrf

            {{-- Logo --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Logo Sekolah</label>
                <div class="flex items-center gap-4">
                    @if($school->logo)
                        <img src="{{ Storage::disk('public')->url($school->logo) }}"
                             class="w-16 h-16 rounded-xl object-cover border border-slate-200">
                    @else
                        <div class="w-16 h-16 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 text-xs">
                            No Logo
                        </div>
                    @endif
                    <input type="file" name="logo" accept=".jpg,.jpeg,.png"
                           class="text-sm text-slate-600">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Sekolah</label>
                <input type="text" name="name" value="{{ old('name', $school->name) }}"
                       class="w-full border border-slate-300 rounded-xl p-3" required>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">
                    Alamat untuk Kop Surat
                    <span class="text-slate-400 font-normal">(jika kosong, pakai alamat umum sekolah)</span>
                </label>
                <textarea name="letterhead_address" rows="2"
                          class="w-full border border-slate-300 rounded-xl p-3">{{ old('letterhead_address', $school->letterhead_address) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Kepala Sekolah</label>
                    <input type="text" name="headmaster_name" value="{{ old('headmaster_name', $school->headmaster_name) }}"
                           class="w-full border border-slate-300 rounded-xl p-3">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">NIP Kepala Sekolah</label>
                    <input type="text" name="headmaster_nip" value="{{ old('headmaster_nip', $school->headmaster_nip) }}"
                           class="w-full border border-slate-300 rounded-xl p-3">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="show_letterhead_on_export" id="show_letterhead"
                       value="1" {{ old('show_letterhead_on_export', $school->show_letterhead_on_export) ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-slate-300 text-blue-600">
                <label for="show_letterhead" class="text-sm text-slate-600">
                    Tampilkan kop surat otomatis di export PDF/Word
                </label>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700">
                    Simpan Kop Surat
                </button>
            </div>
        </form>
    </div>
</x-app-layout>