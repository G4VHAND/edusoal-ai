<x-app-layout>
    <div class="p-6 max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Manajemen Guru</h1>
            <a href="{{ route('admin.teachers.create') }}"
               class="bg-blue-600 text-white px-5 py-2 rounded-xl font-semibold">
                + Tambah Guru
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-slate-600">
                        <th class="px-6 py-4">Nama</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Sekolah</th>
                        <th class="px-6 py-4">Bank Soal</th>
                        <th class="px-6 py-4">Quota Sisa</th>
                        <th class="px-6 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($teachers as $teacher)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-medium text-slate-800">{{ $teacher->name }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $teacher->email }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $teacher->school?->name ?? 'Individual' }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $teacher->question_sets_count }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $teacher->remainingQuota() === -1 ? '∞' : $teacher->remainingQuota() }}</td>
                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    onclick="return confirm('Hapus guru {{ $teacher->name }}?')"
                                    class="text-xs text-red-600 hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                            Belum ada guru terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $teachers->links() }}</div>
    </div>
</x-app-layout>
