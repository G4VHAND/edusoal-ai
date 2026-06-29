<x-app-layout>
    <div class="p-6 max-w-4xl mx-auto">

        <a href="{{ route('admin.individuals.index') }}"
           class="text-blue-600 text-sm mb-4 inline-block">← Kembali ke User Individual</a>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 mb-4">
                {{ session('success') }}
            </div>
        @endif

        {{-- Info user --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ $user->name }}</h1>
                    <p class="text-slate-500 mt-1">{{ $user->email }}</p>
                    <div class="flex items-center gap-3 mt-2">
                        @if($user->email_verified_at)
                            <span class="text-xs text-green-600 font-medium">✓ Email terverifikasi</span>
                        @else
                            <span class="text-xs text-amber-500 font-medium">⏳ Email belum diverifikasi</span>
                        @endif
                        <span class="text-xs text-slate-400">Daftar: {{ $user->created_at->format('d M Y') }}</span>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-sm font-medium
                    {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-3 gap-4 mt-6">
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-sm text-slate-500">Paket</p>
                    <p class="font-semibold text-slate-800 mt-1">{{ $user->subscriptionPlan?->name ?? 'Free' }}</p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-sm text-slate-500">Quota Sisa</p>
                    <p class="font-semibold mt-1 {{ $user->remainingQuota() <= 2 && $user->remainingQuota() !== -1 ? 'text-red-600' : 'text-slate-800' }}">
                        {{ $user->remainingQuota() === -1 ? 'Unlimited' : $user->remainingQuota() . ' generate' }}
                    </p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-sm text-slate-500">Total Bank Soal</p>
                    <p class="font-semibold text-slate-800 mt-1">{{ $user->question_sets_count }}</p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap gap-3 mt-5">

                {{-- Ubah paket --}}
                <form method="POST" action="{{ route('admin.individuals.update-plan', $user) }}"
                      class="flex items-center gap-2">
                    @csrf
                    <select name="plan_slug" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
                        @foreach(\App\Models\SubscriptionPlan::where('is_active', true)->get() as $plan)
                            <option value="{{ $plan->slug }}"
                                {{ $user->subscriptionPlan?->slug == $plan->slug ? 'selected' : '' }}>
                                {{ $plan->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-blue-700">
                        Ubah Paket
                    </button>
                </form>

                {{-- Reset quota --}}
                <form method="POST" action="{{ route('admin.individuals.reset-quota', $user) }}">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Reset quota {{ $user->name }}?')"
                            class="bg-amber-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-amber-600">
                        ⚡ Reset Quota
                    </button>
                </form>

                {{-- Toggle aktif --}}
                <form method="POST" action="{{ route('admin.individuals.toggle-active', $user) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-sm font-medium border transition
                            {{ $user->is_active ? 'border-red-300 text-red-600 hover:bg-red-50' : 'border-green-300 text-green-600 hover:bg-green-50' }}">
                        {{ $user->is_active ? '🔴 Nonaktifkan' : '🟢 Aktifkan' }}
                    </button>
                </form>

                {{-- Hapus akun --}}
                <form method="POST" action="{{ route('admin.individuals.destroy', $user) }}">
                    @csrf @method('DELETE')
                    <button type="submit"
                            onclick="return confirm('Hapus akun {{ $user->name }}? Semua bank soal akan ikut terhapus.')"
                            class="px-4 py-2 rounded-xl text-sm font-medium border border-red-300 text-red-600 hover:bg-red-50">
                        🗑️ Hapus Akun
                    </button>
                </form>
            </div>
        </div>

        {{-- Bank soal terakhir --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="font-bold text-slate-900 mb-4">Bank Soal Terbaru</h2>
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-slate-600">
                        <th class="px-4 py-3">Judul</th>
                        <th class="px-4 py-3">Mapel</th>
                        <th class="px-4 py-3">Jenis</th>
                        <th class="px-4 py-3">Kesulitan</th>
                        <th class="px-4 py-3">Dibuat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($questionSets as $qs)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $qs->title }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $qs->subject }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ $qs->question_type === 'multiple_choice' ? 'bg-blue-100 text-blue-700' : 'bg-violet-100 text-violet-700' }}">
                                {{ $qs->question_type === 'multiple_choice' ? 'Pilihan Ganda' : 'Essay' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ $qs->difficulty === 'mudah' ? 'bg-green-100 text-green-700' : ($qs->difficulty === 'sedang' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                {{ ucfirst($qs->difficulty) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-400 text-xs">{{ $qs->created_at->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada bank soal.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $questionSets->links() }}</div>
        </div>
    </div>
</x-app-layout>