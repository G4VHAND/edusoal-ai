@php
    $totalSubs = $plans->sum('school_subscriptions_count');
    $planColors = ['free' => 'slate', 'basic' => 'blue', 'pro' => 'violet', 'enterprise' => 'amber'];

    $auditIcons = [
        'login' => ['bg' => 'bg-blue-100', 'text' => 'bg-blue-500'],
        'create' => ['bg' => 'bg-emerald-100', 'text' => 'bg-emerald-500'],
        'update' => ['bg' => 'bg-amber-100', 'text' => 'bg-amber-500'],
        'delete' => ['bg' => 'bg-rose-100', 'text' => 'bg-rose-500'],
    ];

    $hour = now()->hour;
    $greeting = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
@endphp

<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full max-w-[1440px] mx-auto px-5 lg:px-8 py-8">

            {{-- ============ HERO ============ --}}
            <div class="relative w-full overflow-hidden rounded-[24px] bg-gradient-to-br from-slate-800 via-slate-900 to-indigo-950 p-8 md:p-10 shadow-lg mb-8">
                <div class="pointer-events-none absolute -top-16 -right-10 w-72 h-72 rounded-full bg-white/5 blur-3xl"></div>
                <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">
                            {{ $greeting }}, {{ explode(' ', auth()->user()->name)[0] }}! 🚀
                        </h1>
                        <p class="text-slate-300 text-sm max-w-xl">
                            Monitor seluruh platform, kelola sekolah, provider AI, dan performa sistem.
                        </p>
                    </div>
                    <a href="{{ route('admin.schools.create') }}"
                       class="inline-flex items-center gap-2 bg-white text-slate-900 font-semibold px-5 py-2.5 text-sm rounded-xl shadow hover:bg-slate-100 transition shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        Tambah Sekolah
                    </a>
                </div>
            </div>

            {{-- ============ KPI CARDS ============ --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-5 mb-8">

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Total Sekolah</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $stats['total_schools'] }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $stats['active_subs'] }} berlangganan aktif</p>
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M12 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Total Guru</p>
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['total_teachers']) }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Seluruh sekolah</p>
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M20 21a8 8 0 1 0-16 0"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">User Individual</p>
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['total_individuals']) }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Non-sekolah</p>
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.81 15.9 9 18.75l-.81-2.85a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.85-.81a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.81 2.85a4.5 4.5 0 0 0 3.09 3.09l2.85.81-2.85.81a4.5 4.5 0 0 0-3.09 3.09Z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Generate AI</p>
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($widgets['generate_this_month']) }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Bulan ini, {{ number_format($widgets['generate_today']) }} hari ini</p>
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 2 3 14h7l-1 8 11-14h-7l1-6Z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Quota AI Digunakan</p>
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($widgets['quota_used_this_month']) }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Generate, bulan ini</p>
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-sky-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Provider Terbanyak</p>
                    <p class="text-lg font-bold text-slate-900">{{ $widgets['top_provider_label'] }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ number_format($widgets['top_provider_count']) }} generate</p>
                </div>

            </div>

            {{-- ============ QUICK ACTION + CHARTS + SIDEBAR ============ --}}
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8 items-start">

                <div class="xl:col-span-2 space-y-6">

                    <div>
                        <h2 class="text-sm font-bold text-slate-900 mb-4">Quick Action</h2>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('admin.schools.create') }}"
                               class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                                <span class="w-6 h-6 rounded-md bg-blue-600 text-white flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                                </span>
                                Tambah Sekolah
                            </a>
                            <a href="{{ route('admin.schools.index') }}"
                               class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                                <span class="w-6 h-6 rounded-md bg-violet-100 text-violet-700 flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                                </span>
                                Kelola Sekolah
                            </a>
                            <a href="{{ route('admin.individuals.index') }}"
                               class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                                <span class="w-6 h-6 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                                </span>
                                User Individual
                            </a>
                            <a href="{{ route('admin.bank-soal.index') }}"
                               class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                                <span class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>
                                </span>
                                Bank Soal
                            </a>
                            <a href="{{ route('admin.audit-log.index') }}"
                               class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                                <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M9 8h6"/><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </span>
                                Audit Log
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[300px] flex flex-col">
                            <h3 class="text-sm font-bold text-slate-900 shrink-0">Generate AI Global</h3>
                            <p class="text-xs text-slate-500 mb-2 shrink-0">6 bulan terakhir, seluruh platform.</p>
                            <div class="flex-1 min-h-0 relative"><canvas id="monthlyGenerateChart"></canvas></div>
                        </div>
                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[300px] flex flex-col">
                            <h3 class="text-sm font-bold text-slate-900 shrink-0">Pertumbuhan Sekolah</h3>
                            <p class="text-xs text-slate-500 mb-2 shrink-0">Sekolah baru per bulan.</p>
                            <div class="flex-1 min-h-0 relative"><canvas id="schoolGrowthChart"></canvas></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[300px] flex flex-col">
                            <h3 class="text-sm font-bold text-slate-900 shrink-0">Pertumbuhan User</h3>
                            <p class="text-xs text-slate-500 mb-2 shrink-0">User baru per bulan (guru + individual).</p>
                            <div class="flex-1 min-h-0 relative"><canvas id="userGrowthChart"></canvas></div>
                        </div>
                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[300px] flex flex-col">
                            <h3 class="text-sm font-bold text-slate-900 shrink-0">AI Provider Usage</h3>
                            <p class="text-xs text-slate-500 mb-2 shrink-0">Sebaran provider AI terpakai.</p>
                            @if(empty($providerChart['labels']))
                                <div class="flex-1 flex items-center justify-center text-xs text-slate-400">Belum ada data</div>
                            @else
                            <div class="flex-1 min-h-0 relative"><canvas id="providerChart"></canvas></div>
                            <div class="shrink-0 space-y-1 mt-2">
                                @php $provPalette = ['#3B82F6', '#8B5CF6', '#F59E0B', '#22C55E']; $provTotal = max(array_sum($providerChart['totals']), 1); @endphp
                                @foreach($providerChart['labels'] as $i => $label)
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="flex items-center gap-1.5 text-slate-600"><span class="w-2 h-2 rounded-full shrink-0" style="background:{{ $provPalette[$i % count($provPalette)] }}"></span>{{ $label }}</span>
                                    <span class="font-semibold text-slate-700">{{ round(($providerChart['totals'][$i] / $provTotal) * 100) }}%</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Distribusi Paket --}}
                    <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                        <h2 class="text-sm font-bold text-slate-900 mb-4">Distribusi Paket Sekolah</h2>
                        <div class="space-y-3">
                            @foreach($plans as $plan)
                            @php
                                $pct = $totalSubs > 0 ? round(($plan->school_subscriptions_count / $totalSubs) * 100) : 0;
                                $color = $planColors[$plan->slug] ?? 'slate';
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                        <span class="w-2 h-2 rounded-full bg-{{ $color }}-500"></span>{{ $plan->name }}
                                    </span>
                                    <span class="text-xs text-slate-500">{{ $plan->school_subscriptions_count }} sekolah · <span class="font-semibold text-{{ $color }}-600">{{ $pct }}%</span></span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-{{ $color }}-500" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Kolom kanan --}}
                <div class="xl:col-span-1 space-y-6">

                    <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-bold text-slate-900">Audit Log Platform</h2>
                            <a href="{{ route('admin.audit-log.index') }}" class="text-blue-600 text-xs font-semibold hover:text-blue-800">Lihat semua</a>
                        </div>
                        @forelse($recentAuditLogs as $log)
                        <div class="flex items-start gap-3 py-2.5 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                            <span class="w-8 h-8 rounded-full {{ $auditIcons[$log->event]['bg'] ?? 'bg-slate-100' }} flex items-center justify-center shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full {{ $auditIcons[$log->event]['text'] ?? 'bg-slate-400' }}"></span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-slate-800 truncate">{{ $log->description }}</p>
                                <p class="text-[11px] text-slate-400">{{ $log->user?->name ?? 'Sistem' }}@if($log->school) · {{ $log->school->name }}@endif · {{ $log->created_at->locale('id')->diffForHumans(null, true) }}</p>
                            </div>
                        </div>
                        @empty
                        <p class="text-xs text-slate-400 text-center py-6">Belum ada aktivitas tercatat.</p>
                        @endforelse
                    </div>

                    <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-bold text-slate-900">Sekolah Terbaru</h2>
                            <a href="{{ route('admin.schools.index') }}" class="text-blue-600 text-xs font-semibold hover:text-blue-800">Lihat semua</a>
                        </div>
                        @forelse($recentSchools->take(5) as $school)
                        <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                            <div class="min-w-0">
                                <a href="{{ route('admin.schools.show', $school) }}" class="text-sm font-semibold text-slate-800 hover:text-blue-600 truncate block">{{ $school->name }}</a>
                                <p class="text-xs text-slate-400">{{ $school->city ?? '-' }}</p>
                            </div>
                            <span class="text-[11px] text-slate-400 shrink-0">{{ $school->created_at->format('d M Y') }}</span>
                        </div>
                        @empty
                        <p class="text-xs text-slate-400 text-center py-6">Belum ada sekolah terdaftar.</p>
                        @endforelse
                    </div>

                </div>
            </div>

        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    function barChart(id, labels, totals, color) {
        const ctx = document.getElementById(id);
        if (!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets: [{ data: totals, backgroundColor: color, borderRadius: 8, maxBarThickness: 36 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } }
            }
        });
    }

    barChart('monthlyGenerateChart', @json($monthlyGenerateChart['labels']), @json($monthlyGenerateChart['totals']), '#2563eb');
    barChart('schoolGrowthChart', @json($schoolGrowthChart['labels']), @json($schoolGrowthChart['totals']), '#8B5CF6');
    barChart('userGrowthChart', @json($userGrowthChart['labels']), @json($userGrowthChart['totals']), '#22C55E');

    const providerCtx = document.getElementById('providerChart');
    if (providerCtx) {
        new Chart(providerCtx, {
            type: 'doughnut',
            data: {
                labels: @json($providerChart['labels']),
                datasets: [{ data: @json($providerChart['totals']), backgroundColor: ['#3B82F6', '#8B5CF6', '#F59E0B', '#22C55E'], borderWidth: 2, borderColor: '#fff' }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } }
        });
    }

});
</script>
</x-app-layout>