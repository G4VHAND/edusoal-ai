<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Admin Panel</h1>
                <p class="text-slate-500 text-sm mt-1">Ringkasan platform EduSoal AI</p>
            </div>
            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.schools.create') }}"
               class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-semibold text-sm">
                + Tambah Sekolah
            </a>
            @endif
        </div>

        {{-- Stats Utama --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            @foreach([
                ['label' => 'Total Sekolah',    'value' => $stats['total_schools'],     'icon' => 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z', 'color' => 'blue'],
                ['label' => 'Total Guru',        'value' => $stats['total_teachers'],    'icon' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M12 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z', 'color' => 'green'],
                ['label' => 'User Individual',   'value' => $stats['total_individuals'], 'icon' => 'M20 21a8 8 0 1 0-16 0M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8z', 'color' => 'violet'],
                ['label' => 'Total Bank Soal',   'value' => $stats['total_questions'],   'icon' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z', 'color' => 'amber'],
                ['label' => 'Subscripsi Aktif',  'value' => $stats['active_subs'],       'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 0 0 1.946-.806 3.42 3.42 0 0 1 4.438 0 3.42 3.42 0 0 0 1.946.806 3.42 3.42 0 0 1 3.138 3.138 3.42 3.42 0 0 0 .806 1.946 3.42 3.42 0 0 1 0 4.438', 'color' => 'slate'],
            ] as $stat)
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                    <div class="w-8 h-8 rounded-lg bg-{{ $stat['color'] }}-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-{{ $stat['color'] }}-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="{{ $stat['icon'] }}"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-slate-900">{{ $stat['value'] }}</p>
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            {{-- Distribusi Paket --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="font-bold text-slate-900 mb-4">Distribusi Paket Langganan</h2>
                <div class="space-y-3">
                    @php
                        $totalSubs = $plans->sum('school_subscriptions_count');
                    @endphp
                    @foreach($plans as $plan)
                    @php
                        $pct = $totalSubs > 0
                            ? round(($plan->school_subscriptions_count / $totalSubs) * 100)
                            : 0;
                        $colors = ['free' => 'slate', 'basic' => 'blue', 'pro' => 'violet', 'enterprise' => 'amber'];
                        $color  = $colors[$plan->slug] ?? 'slate';
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-{{ $color }}-500"></span>
                                <span class="text-sm font-medium text-slate-700">{{ $plan->name }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-slate-500">{{ $plan->school_subscriptions_count }} sekolah</span>
                                <span class="text-xs font-semibold text-{{ $color }}-600 w-8 text-right">{{ $pct }}%</span>
                            </div>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="h-2 rounded-full bg-{{ $color }}-500 transition-all duration-500"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $plan->formattedPriceMonthly() }}/bulan · max {{ $plan->max_teachers === -1 ? '∞' : $plan->max_teachers }} guru · {{ $plan->quota_per_month === -1 ? '∞' : $plan->quota_per_month }} generate/bln</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Aktivitas Terbaru --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="font-bold text-slate-900 mb-4">Ringkasan Fitur per Paket</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-slate-500 border-b border-slate-100">
                                <th class="pb-2">Fitur</th>
                                <th class="pb-2 text-center">Free</th>
                                <th class="pb-2 text-center">Basic</th>
                                <th class="pb-2 text-center">Pro</th>
                                <th class="pb-2 text-center">Ent.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @php
                                $features = [
                                    ['label' => 'Upload Gambar',  'key' => 'allow_image_upload'],
                                    ['label' => 'Export Word',    'key' => 'allow_export_word'],
                                    ['label' => 'Export PDF',     'key' => 'allow_export_pdf'],
                                    ['label' => 'Semua Provider', 'key' => 'allow_all_providers'],
                                ];
                            @endphp
                            @foreach($features as $feat)
                            <tr>
                                <td class="py-2 text-slate-600">{{ $feat['label'] }}</td>
                                @foreach($plans as $plan)
                                <td class="py-2 text-center">
                                    @if($plan->{$feat['key']})
                                        <span class="text-green-500 font-bold">✓</span>
                                    @else
                                        <span class="text-slate-300">–</span>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                            <tr>
                                <td class="py-2 text-slate-600">Quota/bulan</td>
                                @foreach($plans as $plan)
                                <td class="py-2 text-center text-slate-700 font-medium">
                                    {{ $plan->quota_per_month === -1 ? '∞' : $plan->quota_per_month }}
                                </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="py-2 text-slate-600">Max Guru</td>
                                @foreach($plans as $plan)
                                <td class="py-2 text-center text-slate-700 font-medium">
                                    {{ $plan->max_teachers === -1 ? '∞' : $plan->max_teachers }}
                                </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Daftar Sekolah Terbaru --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-slate-900">Sekolah Terbaru</h2>
                @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.schools.index') }}"
                   class="text-sm text-blue-600 hover:underline">
                    Lihat semua →
                </a>
                @endif
            </div>

            @if($recentSchools->isEmpty())
            <div class="text-center py-10 text-slate-400">
                <p class="text-4xl mb-3">🏫</p>
                <p class="font-medium">Belum ada sekolah terdaftar</p>
                @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.schools.create') }}"
                   class="mt-3 inline-block text-sm text-blue-600 hover:underline">
                    Daftarkan sekolah pertama →
                </a>
                @endif
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-slate-500">
                            <th class="px-4 py-3 rounded-l-xl">Sekolah</th>
                            <th class="px-4 py-3">Kota</th>
                            <th class="px-4 py-3">Paket</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 rounded-r-xl">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentSchools as $school)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                @if(auth()->user()->isSuperAdmin())
                                <a href="{{ route('admin.schools.show', $school) }}"
                                   class="font-semibold text-blue-600 hover:underline">
                                    {{ $school->name }}
                                </a>
                                @else
                                <span class="font-semibold text-slate-800">{{ $school->name }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $school->city ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $planName = $school->activeSubscription?->plan?->name ?? 'Trial';
                                    $planColor = match($planName) {
                                        'Free' => 'slate',
                                        'Basic' => 'blue',
                                        'Pro' => 'violet',
                                        'Enterprise' => 'amber',
                                        default => 'slate'
                                    };
                                @endphp
                                <span class="px-2 py-1 bg-{{ $planColor }}-100 text-{{ $planColor }}-700 rounded-full text-xs font-medium">
                                    {{ $planName }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $school->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $school->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if(auth()->user()->isSuperAdmin())
                                <form method="POST"
                                      action="{{ route('admin.schools.toggle-active', $school) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="text-xs px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600">
                                        {{ $school->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

    </div>
</x-app-layout>