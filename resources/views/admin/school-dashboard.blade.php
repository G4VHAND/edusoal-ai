@php
    $isUnlimited = $stats['quota_limit'] === -1;
    $hasQuotaPlan = $isUnlimited || $stats['quota_limit'] > 0;
    $quotaRemaining = $isUnlimited ? '∞' : max(0, $stats['quota_limit'] - $stats['quota_used']);
    $quotaPct = $isUnlimited || $stats['quota_limit'] <= 0
        ? 0
        : min(100, round(($stats['quota_used'] / max($stats['quota_limit'], 1)) * 100));

    $providerKey = $school->resolvedAiProvider();
    $providerNames = ['gemini' => 'Gemini', 'groq' => 'Groq'];
    $providerName = $providerNames[$providerKey] ?? ucfirst($providerKey);

    $prettyModel = function (?string $raw, string $providerKey): string {
        if (!$raw) {
            return '';
        }
        return (string) str($raw)->replace($providerKey.'-', '')->replace('-', ' ')->title();
    };
    $providerModel = $prettyModel(config("ai.providers.{$providerKey}.model"), $providerKey);

    $subjectPalette = ['#3B82F6', '#22C55E', '#F59E0B', '#8B5CF6', '#EF4444', '#06B6D4'];
    $subjectTotalAll = max(array_sum($subjectChart['totals']), 1);
    $qTypeTotal = max(array_sum($questionTypeChart['totals']), 1);

    $auditIcons = [
        'login' => ['bg' => 'bg-blue-100', 'text' => 'bg-blue-500'],
        'create' => ['bg' => 'bg-emerald-100', 'text' => 'bg-emerald-500'],
        'update' => ['bg' => 'bg-amber-100', 'text' => 'bg-amber-500'],
        'delete' => ['bg' => 'bg-rose-100', 'text' => 'bg-rose-500'],
    ];
@endphp

<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full max-w-[1600px] mx-auto px-5 lg:px-8 py-8">

            {{-- ============ HEADER (plain, no gradient banner) ============ --}}
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-1">
                    Selamat Datang, Admin {{ $school->name }}! 👋
                </h1>
                <p class="text-sm text-slate-500">
                    Kelola guru, quota AI, template, dan monitor penggunaan AI di sekolah Anda.
                </p>
            </div>

            {{-- ============ 6 KPI CARDS ============ --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-5 mb-8">

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M12 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm10 14v-2a4 4 0 0 0-3-3.87m-1-11.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Total Guru</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $stats['total_teachers'] }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Terdaftar di sekolah ini</p>
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Bank Soal Sekolah</p>
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['total_questions']) }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Dibuat semua guru</p>
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.81 15.9 9 18.75l-.81-2.85a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.85-.81a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.81 2.85a4.5 4.5 0 0 0 3.09 3.09l2.85.81-2.85.81a4.5 4.5 0 0 0-3.09 3.09Z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Generate AI (Bulan Ini)</p>
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($generateThisMonth) }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Seluruh guru</p>
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 2 3 14h7l-1 8 11-14h-7l1-6Z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Quota AI Sekolah</p>
                    <p class="text-2xl font-bold text-slate-900">
                        {{ $isUnlimited ? '∞' : $quotaPct.'%' }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-0.5 mb-2">
                        {{ $isUnlimited ? 'Unlimited' : number_format($stats['quota_used']).' / '.number_format($stats['quota_limit']) }}
                    </p>
                    @if(!$isUnlimited)
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                        <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ $quotaPct }}%"></div>
                    </div>
                    @endif
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.07-3.694 3.75-8.25 3.75S3.75 8.445 3.75 6.375m16.5 0c0-2.07-3.694-3.75-8.25-3.75S3.75 4.305 3.75 6.375m16.5 0v11.25c0 2.07-3.694 3.75-8.25 3.75s-8.25-1.68-8.25-3.75V6.375"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Paket Langganan</p>
                    <p class="text-lg font-bold text-slate-900 leading-snug line-clamp-2">{{ $plan?->name ?? 'Belum ada paket' }}</p>
                    <p class="text-[11px] mt-0.5 {{ $plan ? 'text-emerald-600 font-medium' : 'text-slate-400' }}">
                        {{ $plan ? 'Aktif' : 'Belum aktif' }}
                    </p>
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">AI Provider Aktif</p>
                    <p class="text-lg font-bold text-slate-900">{{ $providerName }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $providerModel }}</p>
                    <p class="text-[11px] text-emerald-600 font-medium mt-0.5 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                    </p>
                </div>

            </div>

            {{-- ============ QUICK ACTION ============ --}}
            <div class="mb-8">
                <h2 class="text-sm font-bold text-slate-900 mb-4">Quick Action</h2>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.teachers.create') }}"
                       class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                        <span class="w-6 h-6 rounded-md bg-blue-600 text-white flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        </span>
                        Tambah Guru
                    </a>

                    <a href="{{ route('coming-soon', 'import-guru') }}"
                       class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                        <span class="w-6 h-6 rounded-md bg-sky-100 text-sky-700 flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                        </span>
                        Import Guru
                    </a>

                    <a href="{{ route('templates.index') }}"
                       class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                        <span class="w-6 h-6 rounded-md bg-violet-100 text-violet-700 flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </span>
                        Template Dokumen
                    </a>

                    <a href="{{ route('coming-soon', 'subscription-sekolah') }}"
                       class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                        <span class="w-6 h-6 rounded-md bg-rose-100 text-rose-700 flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.25 6.375c0 2.07-3.694 3.75-8.25 3.75S3.75 8.445 3.75 6.375m16.5 0c0-2.07-3.694-3.75-8.25-3.75S3.75 4.305 3.75 6.375m16.5 0v11.25c0 2.07-3.694 3.75-8.25 3.75s-8.25-1.68-8.25-3.75V6.375"/></svg>
                        </span>
                        Subscription
                    </a>

                    <a href="{{ route('admin.audit-log.index') }}"
                       class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                        <span class="w-6 h-6 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h6M9 8h6"/><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </span>
                        Audit Log
                    </a>
                </div>
            </div>

            {{-- ============ MAIN GRID: CHARTS (2/3) + SIDEBAR (1/3) ============ --}}
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">

                <div class="xl:col-span-2 space-y-6">

                    {{-- Row 1: line chart | subject donut | quota gauge --}}
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[300px] flex flex-col">
                            <h3 class="text-sm font-bold text-slate-900 shrink-0">Generate AI per Bulan</h3>
                            <p class="text-xs text-slate-500 mb-2 shrink-0">6 bulan terakhir.</p>
                            <div class="flex-1 min-h-0 relative"><canvas id="schoolMonthlyChart"></canvas></div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[300px] flex flex-col">
                            <h3 class="text-sm font-bold text-slate-900 shrink-0">Distribusi Mata Pelajaran</h3>
                            <p class="text-xs text-slate-500 mb-2 shrink-0">Sebaran bank soal per mapel.</p>
                            @if(empty($subjectChart['labels']))
                                <div class="flex-1 flex items-center justify-center text-xs text-slate-400">Belum ada data</div>
                            @else
                            <div class="flex-1 min-h-0 relative"><canvas id="schoolSubjectChart"></canvas></div>
                            <div class="shrink-0 space-y-1 mt-2 max-h-[60px] overflow-y-auto pr-1">
                                @foreach($subjectChart['labels'] as $i => $label)
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="flex items-center gap-1.5 text-slate-600 truncate">
                                        <span class="w-2 h-2 rounded-full shrink-0" style="background:{{ $subjectPalette[$i % count($subjectPalette)] }}"></span>
                                        {{ $label }}
                                    </span>
                                    <span class="font-semibold text-slate-700">{{ round(($subjectChart['totals'][$i] / $subjectTotalAll) * 100) }}%</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[300px] flex flex-col">
                            <h3 class="text-sm font-bold text-slate-900 shrink-0">Penggunaan Quota AI</h3>
                            <p class="text-xs text-slate-500 mb-2 shrink-0">Reset: {{ optional($school->activeSubscription()->first())->ends_at?->translatedFormat('d F Y') ?? '-' }}</p>
                            <div class="flex-1 min-h-0 relative flex items-center justify-center">
                                @if($hasQuotaPlan)
                                <canvas id="quotaGaugeChart" class="max-h-full"></canvas>
                                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                    <span class="text-2xl font-bold text-slate-900">{{ $isUnlimited ? '∞' : $quotaPct.'%' }}</span>
                                    <span class="text-[10px] text-slate-400">Quota digunakan</span>
                                </div>
                                @else
                                <div class="flex flex-col items-center justify-center text-center px-4">
                                    <span class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-2">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 2 3 14h7l-1 8 11-14h-7l1-6Z"/></svg>
                                    </span>
                                    <p class="text-xs font-semibold text-slate-600">Belum ada quota aktif</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Sekolah belum berlangganan paket</p>
                                </div>
                                @endif
                            </div>
                            <a href="{{ route('coming-soon', 'subscription-sekolah') }}" class="shrink-0 mt-2 text-center text-xs font-semibold text-blue-600 hover:text-blue-800">
                                Lihat Detail →
                            </a>
                        </div>
                    </div>

                    {{-- Row 2: top teachers | question type donut | audit log --}}
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[300px] flex flex-col">
                            <h3 class="text-sm font-bold text-slate-900 shrink-0 mb-1">Guru Paling Aktif</h3>
                            <p class="text-xs text-slate-500 mb-3 shrink-0">Bulan ini.</p>
                            <div class="flex-1 overflow-y-auto">
                                @forelse($topTeachers as $i => $t)
                                @php $max = !empty($topTeachers) ? max(array_column($topTeachers, 'total')) : 1; @endphp
                                <div class="mb-3 last:mb-0">
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="flex items-center gap-2 font-medium text-slate-700 truncate">
                                            <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-[10px] font-bold shrink-0">{{ $i + 1 }}</span>
                                            <span class="truncate">{{ $t['name'] }}</span>
                                        </span>
                                        <span class="text-slate-500 shrink-0">{{ $t['total'] }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                                        <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ round(($t['total'] / $max) * 100) }}%"></div>
                                    </div>
                                </div>
                                @empty
                                <p class="text-xs text-slate-400 text-center py-6">Belum ada aktivitas guru.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[300px] flex flex-col">
                            <h3 class="text-sm font-bold text-slate-900 shrink-0">Distribusi Jenis Soal</h3>
                            <p class="text-xs text-slate-500 mb-2 shrink-0">Pilihan ganda vs essay.</p>
                            <div class="flex-1 min-h-0 relative"><canvas id="schoolQuestionTypeChart"></canvas></div>
                            <div class="shrink-0 space-y-1 mt-2">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="flex items-center gap-1.5 text-slate-600"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Pilihan Ganda</span>
                                    <span class="font-semibold text-slate-700">{{ round(($questionTypeChart['totals'][0] / $qTypeTotal) * 100) }}%</span>
                                </div>
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="flex items-center gap-1.5 text-slate-600"><span class="w-2 h-2 rounded-full bg-violet-500"></span> Essay</span>
                                    <span class="font-semibold text-slate-700">{{ round(($questionTypeChart['totals'][1] / $qTypeTotal) * 100) }}%</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[300px] flex flex-col">
                            <div class="flex items-center justify-between mb-2 shrink-0">
                                <h3 class="text-sm font-bold text-slate-900">Audit Log Terbaru</h3>
                                <a href="{{ route('admin.audit-log.index') }}" class="text-blue-600 text-xs font-semibold hover:text-blue-800">Lihat semua</a>
                            </div>
                            <div class="flex-1 overflow-y-auto">
                                @forelse($recentAuditLogs as $log)
                                <div class="flex items-start gap-2.5 py-2 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                                    <span class="w-7 h-7 rounded-full {{ $auditIcons[$log->event]['bg'] ?? 'bg-slate-100' }} flex items-center justify-center shrink-0">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $auditIcons[$log->event]['text'] ?? 'bg-slate-400' }}"></span>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold text-slate-800 truncate">{{ $log->description }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $log->user?->name ?? 'Sistem' }} · {{ $log->created_at->locale('id')->diffForHumans(null, true) }}</p>
                                    </div>
                                </div>
                                @empty
                                <p class="text-xs text-slate-400 text-center py-6">Belum ada aktivitas.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kolom kanan: Aktivitas Guru Terbaru + Subscription Sekolah --}}
                <div class="xl:col-span-1 space-y-6">

                    <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-bold text-slate-900">Aktivitas Guru Terbaru</h2>
                            <a href="{{ route('admin.bank-soal.index') }}" class="text-blue-600 text-xs font-semibold hover:text-blue-800">Lihat semua</a>
                        </div>
                        @forelse($recentActivity as $act)
                        <div class="flex items-start gap-3 py-2.5 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                            <span class="w-8 h-8 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($act['teacher'], 0, 1)) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-slate-800 truncate">{{ $act['teacher'] }}</p>
                                <p class="text-[11px] text-slate-500 truncate">Generate {{ $act['total_questions'] }} soal {{ $act['subject'] }}</p>
                            </div>
                            <span class="text-[10px] text-slate-400 shrink-0 pt-0.5">
                                {{ $act['created_at']->locale('id')->diffForHumans(null, true) }}
                            </span>
                        </div>
                        @empty
                        <p class="text-xs text-slate-400 text-center py-6">Belum ada aktivitas guru.</p>
                        @endforelse
                    </div>

                    <div class="bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 rounded-2xl p-5 shadow-lg text-white">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-sm font-bold">{{ $plan?->name ?? 'Belum Berlangganan' }}</h2>
                            <svg class="w-4 h-4 text-amber-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.4 6.9L21 9l-5.5 4.3L17.5 21 12 17l-5.5 4 2-7.7L3 9l6.6-.1z"/></svg>
                        </div>
                        @if($plan)
                        <p class="text-xs text-blue-100 mb-4">
                            Aktif hingga {{ optional($school->activeSubscription()->first())->ends_at?->translatedFormat('d F Y') ?? '-' }}
                        </p>
                        <ul class="space-y-1.5 mb-5 text-xs text-blue-50">
                            <li class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-emerald-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Quota AI {{ $plan->quota_per_month === -1 ? 'Unlimited' : number_format($plan->quota_per_month).'/bulan' }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-emerald-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Max {{ $plan->max_teachers === -1 ? 'guru tanpa batas' : $plan->max_teachers.' guru' }}
                            </li>
                        </ul>
                        @else
                        <p class="text-xs text-blue-100 mb-5">Sekolah Anda belum memiliki paket aktif.</p>
                        @endif
                        <a href="{{ route('coming-soon', 'subscription-sekolah') }}"
                           class="block text-center w-full bg-white text-blue-700 font-semibold text-sm py-2.5 rounded-xl hover:bg-blue-50 transition">
                            Kelola Subscription
                        </a>
                    </div>

                </div>
            </div>

            {{-- ============ GURU DI SEKOLAH SAYA ============ --}}
            <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold text-slate-900">Guru di Sekolah Saya</h2>
                    <a href="{{ route('admin.teachers.index') }}" class="text-blue-600 text-xs font-semibold hover:text-blue-800">Lihat semua</a>
                </div>

                @if($recentTeachers->isEmpty())
                <div class="text-center py-8">
                    <p class="text-sm font-semibold text-slate-900 mb-1">Belum ada guru terdaftar</p>
                    <a href="{{ route('admin.teachers.create') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 text-xs rounded-lg font-semibold hover:bg-blue-700 mt-2">
                        Tambah Guru Pertama
                    </a>
                </div>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($recentTeachers as $teacher)
                    <div class="flex items-center gap-3 border border-slate-100 rounded-xl p-3">
                        <span class="w-9 h-9 rounded-full bg-blue-600 text-white text-sm font-bold flex items-center justify-center shrink-0">
                            {{ strtoupper(substr($teacher->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $teacher->name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ $teacher->email }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const monthlyCtx = document.getElementById('schoolMonthlyChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: @json($monthlyGenerateChart['labels']),
                datasets: [{
                    label: 'Generate',
                    data: @json($monthlyGenerateChart['totals']),
                    borderColor: '#7C3AED',
                    backgroundColor: 'rgba(124,58,237,0.1)',
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#7C3AED',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#F1F5F9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const subjectCtx = document.getElementById('schoolSubjectChart');
    if (subjectCtx) {
        const palette = ['#3B82F6', '#22C55E', '#F59E0B', '#8B5CF6', '#EF4444', '#06B6D4'];
        new Chart(subjectCtx, {
            type: 'doughnut',
            data: {
                labels: @json($subjectChart['labels']),
                datasets: [{
                    data: @json($subjectChart['totals']),
                    backgroundColor: @json($subjectChart['labels']).map((_, i) => palette[i % palette.length]),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } }
        });
    }

    const qTypeCtx = document.getElementById('schoolQuestionTypeChart');
    if (qTypeCtx) {
        new Chart(qTypeCtx, {
            type: 'doughnut',
            data: {
                labels: @json($questionTypeChart['labels']),
                datasets: [{
                    data: @json($questionTypeChart['totals']),
                    backgroundColor: ['#3B82F6', '#8B5CF6'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
        });
    }

    const gaugeCtx = document.getElementById('quotaGaugeChart');
    if (gaugeCtx) {
        const used = {{ $isUnlimited ? 0 : $quotaPct }};
        new Chart(gaugeCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [used, 100 - used],
                    backgroundColor: ['#7C3AED', '#F1F5F9'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '75%',
                plugins: { legend: { display: false }, tooltip: { enabled: false } }
            }
        });
    }

});
</script>
</x-app-layout>