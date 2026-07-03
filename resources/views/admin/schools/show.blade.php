<x-app-layout>
    <div class="p-6 max-w-5xl mx-auto">
        <a href="{{ route('admin.schools.index') }}"
           class="text-blue-600 text-sm mb-4 inline-block">← Kembali ke Daftar Sekolah</a>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-200 p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ $school->name }}</h1>
                    <p class="text-slate-500 mt-1">{{ $school->email }} · {{ $school->city }}, {{ $school->province }}</p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm
                    {{ $school->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $school->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-sm text-slate-500">Total Guru</p>
                    <p class="font-semibold text-slate-800 mt-1">{{ $school->users->count() }}</p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-sm text-slate-500">Paket</p>
                    <p class="font-semibold text-slate-800 mt-1">
                        {{ $school->activeSubscription?->plan?->name ?? 'Trial' }}
                    </p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-sm text-slate-500">Quota Sekolah Bulan Ini</p>
                    <p class="font-semibold text-slate-800 mt-1">
                        @if($school->remainingQuota() === -1)
                            ∞ Unlimited
                        @else
                            {{ $school->quotaUsed() }} / {{ $school->quotaLimit() }} terpakai
                        @endif
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">Dipakai bersama semua guru</p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-sm text-slate-500">Aktif Hingga</p>
                    <p class="font-semibold mt-1
                        {{ $school->activeSubscription?->ends_at?->isPast() ? 'text-red-600' : 'text-slate-800' }}">
                        {{ $school->activeSubscription?->ends_at?->format('d M Y') ?? '-' }}
                    </p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-sm text-slate-500">Trial Berakhir</p>
                    <p class="font-semibold text-slate-800 mt-1">
                        {{ $school->trial_ends_at?->format('d M Y') ?? '-' }}
                    </p>
                </div>
            </div>

            @if($school->activeSubscription?->ends_at?->isPast())
                <div class="mt-4 bg-red-50 border border-red-200 rounded-xl p-4">
                    <p class="text-red-800 font-semibold">⚠️ Subscription sudah expired!</p>
                    <p class="text-red-600 text-sm mt-1">Sekolah ini tidak bisa menggunakan platform sampai subscription diperpanjang.</p>
                </div>
            @elseif($school->activeSubscription && !$school->activeSubscription->ends_at->isPast())
                @php $daysLeft = now()->diffInDays($school->activeSubscription->ends_at); @endphp
                @if($daysLeft <= 7)
                <div class="mt-4 bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <p class="text-amber-800 font-semibold">⚠️ Subscription akan berakhir dalam {{ $daysLeft }} hari</p>
                </div>
                @endif
            @endif

            <div class="flex flex-wrap gap-3 mt-5">
                <a href="{{ route('admin.schools.subscription.edit', $school) }}"
                   class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-blue-700 transition">
                    🔄 Perpanjang / Upgrade Subscription
                </a>

                <form method="POST" action="{{ route('admin.schools.reset-quota', $school) }}">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Reset quota semua guru di sekolah ini?')"
                            class="bg-amber-500 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-amber-600 transition">
                        ⚡ Reset Quota Sekolah
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.schools.toggle-active', $school) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl font-semibold text-sm border transition
                            {{ $school->is_active ? 'border-red-300 text-red-600 hover:bg-red-50' : 'border-green-300 text-green-600 hover:bg-green-50' }}">
                        {{ $school->is_active ? '🔴 Nonaktifkan' : '🟢 Aktifkan' }}
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-slate-900">Daftar Guru</h2>
                <a href="{{ route('admin.teachers.create') }}"
                   class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm">
                    + Tambah Guru
                </a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-slate-600">
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Bank Soal</th>
                        <th class="px-4 py-3">Bergabung Sejak</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($teachers as $teacher)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $teacher->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $teacher->email }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">
                                {{ ucfirst($teacher->role) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $teacher->question_sets_count }}</td>
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $teacher->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    onclick="return confirm('Hapus guru ini?')"
                                    class="text-xs text-red-600 hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                            Belum ada guru di sekolah ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $teachers->links() }}</div>
        </div>
    </div>
</x-app-layout>