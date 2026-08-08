@php
    $statusMeta = [
        'active' => ['label' => 'Aktif', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700'],
        'trial' => ['label' => 'Trial', 'bg' => 'bg-blue-50', 'text' => 'text-blue-700'],
        'expired' => ['label' => 'Berakhir', 'bg' => 'bg-rose-50', 'text' => 'text-rose-700'],
        'cancelled' => ['label' => 'Dibatalkan', 'bg' => 'bg-slate-100', 'text' => 'text-slate-500'],
    ];
    $quotaPct = $isUnlimitedQuota || $quotaLimit <= 0 ? 0 : min(100, round(($quotaUsed / max($quotaLimit, 1)) * 100));
@endphp

<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full max-w-[1200px] mx-auto px-5 lg:px-8 py-8">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-1">Subscription</h1>
                <p class="text-sm text-slate-500">Detail paket langganan sekolah {{ $school->name }}.</p>
            </div>

            @if(!$subscription)
            <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-8 text-center mb-8">
                <p class="text-sm font-semibold text-slate-900 mb-1">Belum ada langganan aktif</p>
                <p class="text-xs text-slate-500 mb-4">Sekolah Anda belum berlangganan paket manapun. Hubungi tim kami untuk mulai berlangganan.</p>
                <a href="mailto:support@edusoal.ai" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 text-xs rounded-lg font-semibold hover:bg-blue-700">
                    Hubungi Kami
                </a>
            </div>
            @else
            <div class="bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 rounded-2xl p-6 shadow-lg text-white mb-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <h2 class="text-lg font-bold">{{ $plan->name }}</h2>
                            @php $meta = $statusMeta[$subscription->status] ?? $statusMeta['active']; @endphp
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-white/20">{{ $meta['label'] }}</span>
                        </div>
                        <p class="text-sm text-blue-100">
                            {{ $plan->formattedPriceMonthly() }}/bulan
                            @if($subscription->ends_at) · Berlaku sampai {{ $subscription->ends_at->translatedFormat('d F Y') }} @endif
                        </p>
                    </div>
                    <a href="mailto:support@edusoal.ai" class="bg-white text-blue-700 font-semibold text-xs px-4 py-2 rounded-lg hover:bg-blue-50 transition shrink-0">
                        Hubungi untuk Upgrade
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <p class="text-xs text-slate-500 mb-1">Quota AI Bulan Ini</p>
                    <p class="text-2xl font-bold text-slate-900 mb-2">
                        {{ $isUnlimitedQuota ? 'Unlimited' : number_format($quotaUsed).' / '.number_format($quotaLimit) }}
                    </p>
                    @if(!$isUnlimitedQuota)
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $quotaPct }}%"></div>
                    </div>
                    @endif
                </div>
                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <p class="text-xs text-slate-500 mb-1">Guru Terdaftar</p>
                    <p class="text-2xl font-bold text-slate-900 mb-2">
                        {{ $teacherCount }} @if(!$plan->isUnlimitedTeachers()) / {{ $plan->max_teachers }} @endif
                    </p>
                    @if(!$plan->isUnlimitedTeachers())
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-violet-500 h-2 rounded-full" style="width: {{ min(100, round(($teacherCount / max($plan->max_teachers,1)) * 100)) }}%"></div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 mb-8">
                <h2 class="text-sm font-bold text-slate-900 mb-4">Fitur Paket {{ $plan->name }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    @foreach([
                        'allow_image_upload' => 'Upload gambar untuk soal',
                        'allow_export_word' => 'Export ke Word',
                        'allow_export_pdf' => 'Export ke PDF',
                        'allow_all_providers' => 'Semua AI provider',
                    ] as $key => $label)
                    <div class="flex items-center gap-2 {{ $plan->{$key} ? 'text-slate-700' : 'text-slate-300' }}">
                        <svg class="w-4 h-4 shrink-0 {{ $plan->{$key} ? 'text-emerald-500' : 'text-slate-300' }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            @if($plan->{$key})
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            @endif
                        </svg>
                        {{ $label }}
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="mb-8">
                <h2 class="text-sm font-bold text-slate-900 mb-4">Semua Paket</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ min($availablePlans->count(), 4) }} gap-4">
                    @foreach($availablePlans as $p)
                    <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 {{ $plan && $plan->id === $p->id ? 'ring-2 ring-blue-500' : '' }}">
                        @if($plan && $plan->id === $p->id)
                        <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wide">Paket Anda</span>
                        @endif
                        <p class="text-sm font-bold text-slate-900 mt-1">{{ $p->name }}</p>
                        <p class="text-lg font-bold text-slate-900 mt-1">{{ $p->formattedPriceMonthly() }}<span class="text-xs font-normal text-slate-400">/bulan</span></p>
                        <div class="mt-3 space-y-1 text-xs text-slate-500">
                            <p>{{ $p->isUnlimitedTeachers() ? 'Guru tanpa batas' : $p->max_teachers.' guru' }}</p>
                            <p>{{ $p->isUnlimitedQuota() ? 'Quota unlimited' : number_format($p->quota_per_month).' generate/bulan' }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                <h2 class="text-sm font-bold text-slate-900 mb-4">Riwayat Langganan</h2>
                @if($history->isEmpty())
                <p class="text-xs text-slate-400 text-center py-6">Belum ada riwayat langganan.</p>
                @else
                <div class="divide-y divide-slate-100">
                    @foreach($history as $h)
                    <div class="flex items-center justify-between py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ $h->plan->name ?? '-' }}</p>
                            <p class="text-xs text-slate-400">
                                {{ $h->starts_at?->translatedFormat('d M Y') }} — {{ $h->ends_at?->translatedFormat('d M Y') ?? 'sekarang' }}
                            </p>
                        </div>
                        @php $hMeta = $statusMeta[$h->status] ?? $statusMeta['active']; @endphp
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $hMeta['bg'] }} {{ $hMeta['text'] }}">
                            {{ $hMeta['label'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
