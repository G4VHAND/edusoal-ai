<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">User Individual</h1>
                <p class="text-slate-500 text-sm mt-1">Guru bimbel, tutor, dan dosen yang daftar sendiri</p>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <p class="text-sm text-slate-500">Total Individual</p>
                <p class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <p class="text-sm text-slate-500">Akun Aktif</p>
                <p class="text-3xl font-bold text-green-600 mt-1">{{ $stats['active'] }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <p class="text-sm text-slate-500">Email Terverifikasi</p>
                <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['verified'] }}</p>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 mb-4">
                {{ session('success') }}
            </div>
        @endif

        {{-- Filter --}}
        <form method="GET" class="bg-white rounded-2xl border border-slate-200 p-4 mb-6">
            <div class="flex gap-3">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari nama atau email..."
                       class="flex-1 border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">

                <select name="status" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
                    <option value="">Semua Status</option>
                    <option value="active"   {{ $status == 'active'   ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>

                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-medium">
                    Filter
                </button>
                <a href="{{ route('admin.individuals.index') }}"
                   class="border border-slate-300 text-slate-600 px-4 py-2 rounded-xl text-sm">
                    Reset
                </a>
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-slate-600">
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Verifikasi</th>
                        <th class="px-5 py-3">Paket</th>
                        <th class="px-5 py-3">Quota Sisa</th>
                        <th class="px-5 py-3">Bank Soal</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Daftar</th>
                        <th class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.individuals.show', $user) }}"
                               class="font-medium text-blue-600 hover:underline">
                                {{ $user->name }}
                            </a>
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $user->email }}</td>
                        <td class="px-5 py-3">
                            @if($user->email_verified_at)
                                <span class="text-green-600 text-xs font-medium">✓ Verified</span>
                            @else
                                <span class="text-amber-500 text-xs font-medium">⏳ Pending</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-full text-xs">
                                {{ $user->subscriptionPlan?->name ?? 'Free' }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="{{ $user->remainingQuota() === -1 ? 'text-green-600' : ($user->remainingQuota() <= 2 ? 'text-red-600' : 'text-slate-600') }} font-medium text-sm">
                                {{ $user->remainingQuota() === -1 ? '∞' : $user->remainingQuota() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $user->question_sets_count }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-400 text-xs">
                            {{ $user->created_at->format('d M Y') }}
                        </td>
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.individuals.show', $user) }}"
                               class="text-blue-600 hover:underline text-xs font-medium">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-5 py-12 text-center text-slate-400">
                            Belum ada user individual.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>
</x-app-layout>