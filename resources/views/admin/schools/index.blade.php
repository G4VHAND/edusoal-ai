<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Manajemen Sekolah</h1>
            <a href="{{ route('admin.schools.create') }}"
               class="bg-blue-600 text-white px-5 py-2 rounded-xl font-semibold">
                + Tambah Sekolah
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
                        <th class="px-6 py-4">Sekolah</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Kota</th>
                        <th class="px-6 py-4">Guru</th>
                        <th class="px-6 py-4">Paket</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($schools as $school)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.schools.show', $school) }}"
                               class="font-semibold text-blue-600 hover:underline">
                                {{ $school->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $school->email }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $school->city ?? '-' }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $school->users_count }}</td>
                        <td class="px-6 py-4">
                            @php $planName = $school->activeSubscription?->plan?->name; @endphp
                            <span class="px-2 py-1 rounded-full text-xs {{ $planName ? 'bg-violet-100 text-violet-700' : 'bg-red-100 text-red-700' }}">
                                {{ $planName ?? 'Tidak Ada' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ $school->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $school->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST"
                                  action="{{ route('admin.schools.toggle-active', $school) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                    class="text-xs px-3 py-1 rounded-lg border border-slate-300 hover:bg-slate-100">
                                    {{ $school->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                            Belum ada sekolah terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $schools->links() }}</div>
    </div>
</x-app-layout>