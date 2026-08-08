<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full max-w-[800px] mx-auto px-5 lg:px-8 py-8">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-1">Hasil Import Guru</h1>
                <p class="text-sm text-slate-500">
                    {{ count($created) }} guru berhasil ditambahkan
                    @if(count($failed)) · {{ count($failed) }} baris gagal @endif
                </p>
            </div>

            @if(count($created))
            <div class="bg-amber-50 text-amber-800 text-xs font-medium px-4 py-3 rounded-xl mb-4">
                ⚠️ Simpan/salin password di bawah ini sekarang — halaman ini tidak akan menampilkannya lagi setelah kamu pindah halaman.
            </div>

            <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 overflow-hidden mb-6">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold">Nama</th>
                            <th class="text-left px-4 py-3 font-semibold">Email</th>
                            <th class="text-left px-4 py-3 font-semibold">Password</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($created as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $row['name'] }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row['email'] }}</td>
                            <td class="px-4 py-3 font-mono text-slate-800">{{ $row['password'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if(count($failed))
            <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 overflow-hidden mb-6">
                <div class="px-4 py-3 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-rose-700">Baris yang Gagal</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold">Baris</th>
                            <th class="text-left px-4 py-3 font-semibold">Email</th>
                            <th class="text-left px-4 py-3 font-semibold">Alasan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($failed as $row)
                        <tr>
                            <td class="px-4 py-3 text-slate-500">#{{ $row['row'] }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row['email'] }}</td>
                            <td class="px-4 py-3 text-rose-600">{{ $row['reason'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if(count($created) === 0 && count($failed) === 0)
            <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-8 text-center">
                <p class="text-sm text-slate-500">File CSV kosong atau tidak ada baris data.</p>
            </div>
            @endif

            <div class="flex gap-3">
                <a href="{{ route('admin.teachers.index') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold">
                    Lihat Daftar Guru
                </a>
                <a href="{{ route('admin.teachers.import') }}"
                   class="px-5 py-2.5 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-100">
                    Import Lagi
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
