@php
    $currentUser = auth()->user();
    $canGenerate = $currentUser->isTeacher() || $currentUser->isIndividual();

    // AI provider yang sedang aktif dipakai user ini.
    $providerKey = $currentUser->hasSchool()
        ? $currentUser->school->resolvedAiProvider()
        : config('ai.default');
    // Ubah string model mentah (mis. "gemini-2.5-flash") jadi label yang enak
    // dibaca (mis. "2.5 Flash") tanpa mengulang nama provider-nya.
    $prettyModel = function (?string $raw, string $providerKey): string {
        if (!$raw) {
            return '';
        }
        $label = str($raw)->replace($providerKey.'-', '')->replace('-', ' ')->title();

        return (string) $label;
    };

    $providerMeta = [
        'gemini' => ['name' => 'Gemini', 'model' => $prettyModel(config('ai.providers.gemini.model'), 'gemini')],
        'groq' => ['name' => 'Groq', 'model' => $prettyModel(config('ai.providers.groq.model'), 'groq')],
    ];
    $providerName = $providerMeta[$providerKey]['name'] ?? ucfirst($providerKey);
    $providerModel = $providerMeta[$providerKey]['model'] ?? '';

    // Paket & tanggal berakhir.
    $planExpiry = $currentUser->hasSchool()
        ? optional($currentUser->school->activeSubscription()->first())->ends_at
        : $currentUser->subscription_ends_at;

    // Ada kasus di mana user belum punya langganan sama sekali (limit = 0,
    // bukan unlimited) — jangan tampilkan "8 / 0" atau "Status Aktif" yang
    // menyesatkan untuk kasus ini.
    $hasActivePlan = $quotaWidget['unlimited'] || $quotaWidget['limit'] > 0;

    // Palet warna donut mata pelajaran + persentase untuk legend custom.
    $subjectPalette = ['#3B82F6', '#22C55E', '#F59E0B', '#8B5CF6', '#EF4444', '#06B6D4', '#EC4899', '#84CC16'];
    $subjectTotalAll = max($subjectStats->sum('total'), 1);

    // Trend "dari bulan lalu" — dihitung dari data bulanan asli ($monthlyTotals),
    // bukan angka karangan. Hanya ditampilkan kalau datanya cukup (>= 2 bulan).
    $hasTrend = count($monthlyTotals) >= 2;
    $setsDelta = $hasTrend ? end($monthlyTotals) - $monthlyTotals[count($monthlyTotals) - 2] : 0;
    $avgQuestionsPerSet = $totalQuestionSets > 0 ? $totalQuestions / $totalQuestionSets : 0;
    $questionsDelta = (int) round($setsDelta * $avgQuestionsPerSet);
@endphp

@php
    $trendBadge = function (int $delta) {
        if ($delta === 0) {
            return ['icon' => '→', 'color' => 'text-slate-400', 'text' => 'sama seperti bulan lalu'];
        }
        return $delta > 0
            ? ['icon' => '↗', 'color' => 'text-emerald-600', 'text' => $delta.' dari bulan lalu']
            : ['icon' => '↘', 'color' => 'text-red-500', 'text' => abs($delta).' dari bulan lalu'];
    };
@endphp

<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full max-w-[1440px] mx-auto px-5 lg:px-8 py-8">

            {{-- ============ HERO ============ --}}
            <div class="relative w-full overflow-hidden rounded-[24px] bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700 p-8 md:p-10 shadow-lg mb-8">
                <div class="pointer-events-none absolute -top-16 -right-10 w-72 h-72 rounded-full bg-white/10 blur-3xl"></div>
                <div class="pointer-events-none absolute bottom-0 left-1/3 w-56 h-56 rounded-full bg-white/10 blur-3xl"></div>

                <div class="relative z-10 flex flex-col lg:flex-row items-center gap-6">

                    <div class="w-full lg:w-[38%]">
                        @php
                            $hour = now()->hour;
                            $greeting = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
                            $greetingIcon = $hour < 11 ? '☀️' : ($hour < 18 ? '🌤️' : '🌙');
                        @endphp
                        <h1 class="text-2xl md:text-3xl font-bold text-white mb-2 flex items-center gap-2">
                            <span>{{ $greetingIcon }}</span>
                            {{ $greeting }}, {{ explode(' ', $currentUser->name)[0] }}! 👋
                        </h1>

                        <p class="text-blue-100 text-sm mb-5 max-w-md">
                            EduSoal AI siap membantu Anda membuat soal berkualitas sesuai kurikulum.
                        </p>

                        <div class="flex flex-wrap gap-3 mb-6">
                            <div class="inline-flex items-center gap-2 bg-white/15 backdrop-blur-sm border border-white/10 text-white px-3.5 py-2 rounded-xl text-xs">
                                <svg class="w-4 h-4 text-violet-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z"/>
                                </svg>
                                <span>
                                    <span class="block text-blue-100/80">AI Provider Aktif</span>
                                    <span class="block font-semibold">{{ $providerName }} {{ $providerModel }}</span>
                                </span>
                            </div>

                            <div class="inline-flex items-center gap-2 bg-white/15 backdrop-blur-sm border border-white/10 text-white px-3.5 py-2 rounded-xl text-xs">
                                <svg class="w-4 h-4 text-blue-100" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 15l4-5 3 3 5-6"/>
                                </svg>
                                <span>
                                    <span class="block text-blue-100/80">Generate Bulan Ini</span>
                                    <span class="block font-semibold">{{ $aiGeneratedCount }} kali</span>
                                </span>
                            </div>
                        </div>

                        @if($canGenerate)
                        <a href="{{ route('generate-soal') }}"
                           class="inline-flex items-center gap-2 bg-white text-blue-700 font-semibold px-5 py-2.5 text-sm rounded-xl shadow hover:bg-blue-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                            </svg>
                            Generate Soal
                        </a>
                        @else
                        <a href="{{ route('admin.bank-soal.index') }}"
                           class="inline-flex items-center gap-2 bg-white text-blue-700 font-semibold px-5 py-2.5 text-sm rounded-xl shadow hover:bg-blue-50 transition">
                            Lihat Bank Soal Sekolah
                        </a>
                        @endif
                    </div>

                    <div class="w-full lg:w-[38%] flex items-center justify-center order-3 lg:order-2">
                        <svg viewBox="0 0 460 320" class="w-full max-w-sm h-auto select-none" aria-hidden="true">
                            <defs>
                                <linearGradient id="robotHeadGradient" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#EFF6FF"/>
                                    <stop offset="100%" stop-color="#DBEAFE"/>
                                </linearGradient>
                                <linearGradient id="robotBodyGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#FFFFFF"/>
                                    <stop offset="100%" stop-color="#EEF2FF"/>
                                </linearGradient>
                                <linearGradient id="laptopScreenGradient" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#6366F1"/>
                                    <stop offset="100%" stop-color="#4338CA"/>
                                </linearGradient>
                                <radialGradient id="screenGlow" cx="50%" cy="35%" r="65%">
                                    <stop offset="0%" stop-color="#A5B4FC" stop-opacity="0.55"/>
                                    <stop offset="100%" stop-color="#A5B4FC" stop-opacity="0"/>
                                </radialGradient>
                                <filter id="softShadow" x="-40%" y="-40%" width="180%" height="180%">
                                    <feDropShadow dx="0" dy="10" stdDeviation="10" flood-color="#1E1B4B" flood-opacity="0.25"/>
                                </filter>
                            </defs>

                            <ellipse cx="230" cy="290" rx="150" ry="14" fill="#00000018"/>

                            <g fill="#FFFFFF" opacity="0.9">
                                <path d="M66 60l3 8 8 3-8 3-3 8-3-8-8-3 8-3z"/>
                                <path d="M398 45l2.5 6.5L407 54l-6.5 2.5L398 63l-2.5-6.5L389 54l6.5-2.5z"/>
                                <circle cx="52" cy="150" r="3"/>
                                <circle cx="412" cy="165" r="3.5"/>
                                <circle cx="90" cy="230" r="2.5"/>
                            </g>

                            {{-- floating checklist card --}}
                            <g transform="translate(322,58)" filter="url(#softShadow)">
                                <rect width="82" height="64" rx="14" fill="#FFFFFF"/>
                                <rect x="13" y="15" width="42" height="7" rx="3.5" fill="#C7D2FE"/>
                                <rect x="13" y="28" width="56" height="5.5" rx="2.75" fill="#E0E7FF"/>
                                <rect x="13" y="38" width="56" height="5.5" rx="2.75" fill="#E0E7FF"/>
                                <rect x="13" y="48" width="36" height="5.5" rx="2.75" fill="#E0E7FF"/>
                            </g>

                            {{-- floating check bubble --}}
                            <g transform="translate(338,128)" filter="url(#softShadow)">
                                <circle cx="22" cy="22" r="22" fill="#34D399"/>
                                <path d="M12 22l7 7 13-14" stroke="white" stroke-width="3.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                            </g>

                            {{-- laptop on lap --}}
                            <g transform="translate(118,196)" filter="url(#softShadow)">
                                <rect x="-6" y="98" width="204" height="12" rx="6" fill="#1E1B4B"/>
                                <rect x="0" y="0" width="192" height="102" rx="12" fill="#312E81"/>
                                <rect x="8" y="8" width="176" height="80" rx="5" fill="url(#laptopScreenGradient)"/>
                                <rect x="8" y="8" width="176" height="80" rx="5" fill="url(#screenGlow)"/>
                                <rect x="20" y="20" width="66" height="9" rx="4.5" fill="#C7D2FE"/>
                                <rect x="20" y="36" width="118" height="6.5" rx="3.25" fill="#A5B4FC"/>
                                <rect x="20" y="48" width="88" height="6.5" rx="3.25" fill="#A5B4FC"/>
                                <rect x="20" y="60" width="108" height="6.5" rx="3.25" fill="#A5B4FC"/>
                            </g>

                            {{-- robot sitting cross-legged --}}
                            <g transform="translate(148,52)">
                                {{-- folded legs peeking beside the laptop --}}
                                <ellipse cx="8" cy="228" rx="26" ry="16" fill="#E2E8F0"/>
                                <ellipse cx="164" cy="228" rx="26" ry="16" fill="#E2E8F0"/>

                                {{-- antenna --}}
                                <line x1="72" y1="-2" x2="72" y2="16" stroke="#CBD5E1" stroke-width="4" stroke-linecap="round"/>
                                <circle cx="72" cy="-6" r="7.5" fill="#38BDF8"/>
                                <circle cx="72" cy="-6" r="3" fill="#E0F2FE"/>

                                {{-- head --}}
                                <rect x="20" y="16" width="104" height="76" rx="30" fill="url(#robotHeadGradient)" filter="url(#softShadow)"/>
                                <rect x="37" y="42" width="70" height="32" rx="16" fill="#1E293B"/>
                                <circle cx="59" cy="58" r="7" fill="#7DD3FC"/>
                                <circle cx="85" cy="58" r="7" fill="#7DD3FC"/>
                                <circle cx="61" cy="55.5" r="2.2" fill="#F0FDFF"/>
                                <circle cx="87" cy="55.5" r="2.2" fill="#F0FDFF"/>
                                <path d="M63 79a9 9 0 0 0 16 0" stroke="#1E293B" stroke-width="3" fill="none" stroke-linecap="round"/>
                                {{-- blush --}}
                                <ellipse cx="40" cy="70" rx="7" ry="4.5" fill="#FBCFE8" opacity="0.8"/>
                                <ellipse cx="104" cy="70" rx="7" ry="4.5" fill="#FBCFE8" opacity="0.8"/>

                                {{-- arms hugging the laptop --}}
                                <rect x="-8" y="118" width="20" height="52" rx="10" fill="#E2E8F0" transform="rotate(8 2 144)"/>
                                <rect x="132" y="118" width="20" height="52" rx="10" fill="#E2E8F0" transform="rotate(-8 142 144)"/>

                                {{-- torso --}}
                                <rect x="10" y="100" width="124" height="98" rx="30" fill="url(#robotBodyGradient)" filter="url(#softShadow)"/>
                                <circle cx="72" cy="148" r="18" fill="#DBEAFE"/>
                                <path d="M72 137v22M61 148h22" stroke="#3B82F6" stroke-width="3.5" stroke-linecap="round"/>
                            </g>
                        </svg>
                    </div>

                    {{-- Progress bulan ini — kolom sendiri, bukan absolute, supaya tidak pernah ke-clip --}}
                    <div class="w-full lg:w-[24%] order-2 lg:order-3">
                        <div class="bg-white/95 rounded-2xl p-4 shadow-lg">
                            <p class="text-xs font-semibold text-slate-500 mb-1">Progress Bulan Ini</p>
                            <p class="text-2xl font-bold text-slate-900 mb-2">
                                @if($hasActivePlan)
                                    {{ $aiGeneratedCount }} <span class="text-sm font-medium text-slate-400">/ {{ $quotaWidget['unlimited'] ? '∞' : $quotaWidget['limit'] }}</span>
                                @else
                                    {{ $aiGeneratedCount }}
                                @endif
                            </p>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-medium text-slate-500">Generate</span>
                                @if($hasActivePlan && !$quotaWidget['unlimited'])
                                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                    {{ round(($aiGeneratedCount / max($quotaWidget['limit'],1)) * 100) }}%
                                </span>
                                @endif
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 mb-2">
                                <div class="bg-gradient-to-r from-blue-500 to-violet-500 h-2 rounded-full"
                                     style="width: {{ !$hasActivePlan ? 0 : ($quotaWidget['unlimited'] ? 100 : min(100, round(($aiGeneratedCount / max($quotaWidget['limit'] ?: 1, 1)) * 100))) }}%"></div>
                            </div>
                            <p class="text-[11px] text-slate-400">
                                {{ $hasActivePlan ? 'Ayo kejar target bulan ini! 💪' : 'Belum ada paket aktif' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ FILTER PERIODE ============ --}}
            <div class="flex flex-wrap items-center gap-2 mb-5">
                <span class="text-xs font-medium text-slate-400 mr-1">Periode:</span>
                @php
                    $periods = ['all' => 'Semua Data', '7days' => '7 Hari', '30days' => '30 Hari', 'year' => 'Tahun Ini'];
                @endphp
                @foreach($periods as $key => $label)
                <a href="{{ route('dashboard', ['period' => $key]) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-medium transition
                   {{ $period == $key ? 'bg-blue-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>

            {{-- ============ 6 KPI CARDS ============ --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-5 mb-8">

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.04A8.97 8.97 0 0 0 6 3.75c-1.05 0-2.06.18-3 .51v14.25A8.99 8.99 0 0 1 6 18c2.3 0 4.41.87 6 2.29m0-14.25a8.97 8.97 0 0 1 6-2.29c1.05 0 2.06.18 3 .51v14.25A8.99 8.99 0 0 0 18 18a8.97 8.97 0 0 0-6 2.29V6.04Z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Bank Soal</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $totalQuestionSets }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Total bank soal</p>
                    @if($hasTrend)
                        @php $b = $trendBadge($setsDelta); @endphp
                        <p class="text-[11px] mt-1 {{ $b['color'] }} font-semibold">{{ $b['icon'] }} {{ $b['text'] }}</p>
                    @endif
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.75a3.75 3.75 0 1 1 5.34 3.4c-.8.38-1.59 1.14-1.59 2.02V16.5M12 19.5h.008v.008H12V19.5ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Pertanyaan</p>
                    <p class="text-2xl font-bold text-slate-900">{{ number_format($totalQuestions) }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Total pertanyaan</p>
                    @if($hasTrend)
                        @php $b = $trendBadge($questionsDelta); @endphp
                        <p class="text-[11px] mt-1 {{ $b['color'] }} font-semibold">{{ $b['icon'] }} {{ $b['text'] }}</p>
                    @endif
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.81 15.9 9 18.75l-.81-2.85a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.85-.81a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.81 2.85a4.5 4.5 0 0 0 3.09 3.09l2.85.81-2.85.81a4.5 4.5 0 0 0-3.09 3.09Z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Generate AI</p>
                    <p class="text-2xl font-bold text-slate-900">{{ $aiGeneratedCount }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Total generate</p>
                    @if($hasTrend)
                        @php $b = $trendBadge($setsDelta); @endphp
                        <p class="text-[11px] mt-1 {{ $b['color'] }} font-semibold">{{ $b['icon'] }} {{ $b['text'] }}</p>
                    @endif
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 2 3 14h7l-1 8 11-14h-7l1-6Z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Quota Tersisa</p>
                    <p class="text-2xl font-bold text-slate-900">
                        {{ $quotaWidget['unlimited'] ? '∞' : $quotaWidget['value'] }}
                        @if(!$quotaWidget['unlimited'])<span class="text-sm font-medium text-slate-400">/ {{ $quotaWidget['limit'] }}</span>@endif
                    </p>
                    <p class="text-[11px] text-slate-400 mt-0.5 mb-2">
                        {{ $quotaWidget['unlimited'] ? 'Unlimited' : round((max($quotaWidget['value'],0) / max($quotaWidget['limit'],1)) * 100).'% tersisa' }}
                    </p>
                    @if(!$quotaWidget['unlimited'])
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                        <div class="bg-amber-500 h-1.5 rounded-full"
                             style="width: {{ min(100, round((max($quotaWidget['value'],0) / max($quotaWidget['limit'],1)) * 100)) }}%"></div>
                    </div>
                    @endif
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.07-3.694 3.75-8.25 3.75S3.75 8.445 3.75 6.375m16.5 0c0-2.07-3.694-3.75-8.25-3.75S3.75 4.305 3.75 6.375m16.5 0v11.25c0 2.07-3.694 3.75-8.25 3.75s-8.25-1.68-8.25-3.75V6.375"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Paket Anda</p>
                    <p class="text-base font-bold text-slate-900 leading-snug line-clamp-2">{{ $quotaWidget['plan_name'] }}</p>
                    <p class="text-[11px] mt-1 {{ $hasActivePlan ? 'text-emerald-600 font-medium' : 'text-slate-400' }}">
                        {{ $hasActivePlan ? 'Status Aktif' : 'Belum aktif' }}
                    </p>
                    @if($planExpiry)
                    <p class="text-[11px] text-rose-600 font-semibold mt-0.5">Hingga {{ $planExpiry->translatedFormat('d F Y') }}</p>
                    @endif
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">AI Provider</p>
                    <p class="text-lg font-bold text-slate-900">{{ $providerName }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $providerModel }}</p>
                    <p class="text-[11px] text-emerald-600 font-medium mt-0.5 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Status Aktif
                    </p>
                </div>

            </div>


            {{-- ============ MAIN GRID: AKSI CEPAT + STATISTIK + AKTIVITAS ============ --}}
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8 items-start">

                <div class="xl:col-span-2 space-y-6">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 mb-4">Aksi Cepat</h2>
                        <div class="flex flex-wrap gap-2">
                            @if($canGenerate)
                            <a href="{{ route('generate-soal') }}"
                               class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                                <span class="w-6 h-6 rounded-md bg-blue-600 text-white flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                                </span>
                                Generate Soal
                            </a>
                            @endif

                            <a href="{{ $canGenerate ? route('bank-soal') : route('admin.bank-soal.index') }}"
                               class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                                <span class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>
                                </span>
                                Bank Soal
                            </a>

                            @if($currentUser->isIndividual())
                            <a href="{{ route('templates.index') }}"
                               class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                                <span class="w-6 h-6 rounded-md bg-violet-100 text-violet-700 flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </span>
                                Template Dokumen
                            </a>
                            @endif

                            <a href="{{ route('profile.edit') }}"
                               class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                                <span class="w-6 h-6 rounded-md bg-amber-100 text-amber-700 flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                                </span>
                                Profil &amp; Pengaturan
                            </a>

                            <a href="{{ route('coming-soon', 'materi-pembelajaran') }}"
                               class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700 hover:bg-blue-50/50 transition whitespace-nowrap">
                                <span class="w-6 h-6 rounded-md bg-rose-100 text-rose-700 flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.04A8.97 8.97 0 0 0 6 3.75c-1.05 0-2.06.18-3 .51v14.25A8.99 8.99 0 0 1 6 18c2.3 0 4.41.87 6 2.29m0-14.25a8.97 8.97 0 0 1 6-2.29c1.05 0 2.06.18 3 .51v14.25A8.99 8.99 0 0 0 18 18a8.97 8.97 0 0 0-6 2.29V6.04Z"/></svg>
                                </span>
                                Materi Pembelajaran
                            </a>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Statistik Penggunaan</h2>
                        <p class="text-xs text-slate-500">Ringkasan performa bank soal dan aktivitas generate Anda.</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[340px] flex flex-col">
                            <h3 class="text-sm font-bold text-slate-900 shrink-0">Generate Soal per Bulan</h3>
                            <p class="text-xs text-slate-500 mb-2 shrink-0">Jumlah bank soal yang dibuat setiap bulan.</p>
                            <div class="flex-1 min-h-0 relative">
                                <canvas id="monthlyActivityChart"></canvas>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[340px] flex flex-col">
                            <h3 class="text-sm font-bold text-slate-900 shrink-0">Distribusi Mata Pelajaran</h3>
                            <p class="text-xs text-slate-500 mb-2 shrink-0">Sebaran bank soal per mapel.</p>
                            <div class="flex-1 min-h-0 relative">
                                <canvas id="subjectChart"></canvas>
                            </div>
                            @if($subjectStats->isNotEmpty())
                            <div class="shrink-0 space-y-1 mt-2 max-h-[70px] overflow-y-auto pr-1">
                                @foreach($subjectStats->take(6) as $i => $s)
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="flex items-center gap-1.5 text-slate-600 truncate">
                                        <span class="w-2 h-2 rounded-full shrink-0" style="background:{{ $subjectPalette[$i % count($subjectPalette)] }}"></span>
                                        {{ $s->subject }}
                                    </span>
                                    <span class="font-semibold text-slate-700">{{ round(($s->total / $subjectTotalAll) * 100) }}%</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        @php
                            $questionTypeTotal = max($totalMultipleChoice + $totalEssay, 1);
                            $difficultyTotal = max($easyCount + $mediumCount + $hardCount, 1);
                        @endphp

                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[300px] flex flex-col">
                            <h3 class="text-sm font-bold text-slate-900 shrink-0">Jenis Soal</h3>
                            <p class="text-xs text-slate-500 mb-2 shrink-0">Pilihan ganda vs essay.</p>
                            <div class="flex-1 min-h-0 relative">
                                <canvas id="questionTypeChart"></canvas>
                            </div>
                            <div class="shrink-0 space-y-1 mt-2">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="flex items-center gap-1.5 text-slate-600">
                                        <span class="w-2 h-2 rounded-full shrink-0 bg-blue-500"></span> Pilihan Ganda
                                    </span>
                                    <span class="font-semibold text-slate-700">{{ round(($totalMultipleChoice / $questionTypeTotal) * 100) }}%</span>
                                </div>
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="flex items-center gap-1.5 text-slate-600">
                                        <span class="w-2 h-2 rounded-full shrink-0 bg-violet-500"></span> Essay
                                    </span>
                                    <span class="font-semibold text-slate-700">{{ round(($totalEssay / $questionTypeTotal) * 100) }}%</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 h-[300px] flex flex-col">
                            <h3 class="text-sm font-bold text-slate-900 shrink-0">Tingkat Kesulitan</h3>
                            <p class="text-xs text-slate-500 mb-2 shrink-0">Distribusi mudah, sedang, sulit.</p>
                            <div class="flex-1 min-h-0 relative">
                                <canvas id="difficultyChart"></canvas>
                            </div>
                            <div class="shrink-0 space-y-1 mt-2">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="flex items-center gap-1.5 text-slate-600">
                                        <span class="w-2 h-2 rounded-full shrink-0 bg-green-500"></span> Mudah
                                    </span>
                                    <span class="font-semibold text-slate-700">{{ round(($easyCount / $difficultyTotal) * 100) }}%</span>
                                </div>
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="flex items-center gap-1.5 text-slate-600">
                                        <span class="w-2 h-2 rounded-full shrink-0 bg-amber-500"></span> Sedang
                                    </span>
                                    <span class="font-semibold text-slate-700">{{ round(($mediumCount / $difficultyTotal) * 100) }}%</span>
                                </div>
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="flex items-center gap-1.5 text-slate-600">
                                        <span class="w-2 h-2 rounded-full shrink-0 bg-red-500"></span> Sulit
                                    </span>
                                    <span class="font-semibold text-slate-700">{{ round(($hardCount / $difficultyTotal) * 100) }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Aktivitas Terbaru</h2>
                            <p class="text-xs text-slate-500">Bank soal terakhir dibuat.</p>
                        </div>
                        <a href="{{ $canGenerate ? route('bank-soal') : route('admin.bank-soal.index') }}"
                           class="text-blue-600 text-xs font-semibold hover:text-blue-800 shrink-0">
                            Lihat semua
                        </a>
                    </div>

                    @forelse($latestQuestionSets as $index => $item)
                        <a href="{{ route('bank-soal.show', $item['id']) }}"
                           class="flex items-start gap-3 py-3 {{ !$loop->last ? 'border-b border-slate-100' : '' }} group">
                            <span class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                                </svg>
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-900 group-hover:text-blue-700 transition truncate">
                                    Generate bank soal {{ $item['subject'] }}
                                </p>
                                <p class="text-xs text-slate-500 truncate">{{ $item['title'] }}</p>
                            </div>

                            <span class="text-[11px] text-slate-400 shrink-0 pt-0.5">
                                {{ \Illuminate\Support\Carbon::parse($item['created_at'])->locale('id')->diffForHumans(null, true) }}
                            </span>
                        </a>
                    @empty
                        <div class="text-center py-10">
                            <p class="text-sm font-semibold text-slate-900 mb-1">Belum ada aktivitas</p>
                            <p class="text-xs text-slate-500 mb-4">Bank soal yang Anda buat akan muncul di sini.</p>
                            @if($canGenerate)
                            <a href="{{ route('generate-soal') }}"
                               class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 text-xs rounded-lg font-semibold hover:bg-blue-700">
                                Generate Soal Pertama
                            </a>
                            @endif
                        </div>
                    @endforelse
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <h2 class="text-sm font-bold text-slate-900 mb-4">AI Information</h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 text-slate-500">
                                <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/></svg>
                                Provider
                            </span>
                            <span class="font-semibold text-slate-800">{{ $providerName }} {{ $providerModel }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 text-slate-500">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/></svg>
                                Total Generate
                            </span>
                            <span class="font-semibold text-slate-800">{{ $aiGeneratedCount }} kali</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 text-slate-500">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/></svg>
                                Rata-rata Waktu
                            </span>
                            <span class="font-semibold text-slate-800">± 8 detik</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 text-slate-500">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/></svg>
                                Status
                            </span>
                            <span class="inline-flex items-center gap-1 font-semibold text-emerald-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-slate-900">Subscription</h2>
                        <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.4 6.9L21 9l-5.5 4.3L17.5 21 12 17l-5.5 4 2-7.7L3 9l6.6-.1z"/></svg>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Paket</span>
                            <span class="font-semibold text-slate-800">{{ $quotaWidget['plan_name'] }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Quota</span>
                            <span class="font-semibold text-slate-800">
                                {{ $quotaWidget['unlimited'] ? 'Unlimited' : $quotaWidget['value'].' / '.$quotaWidget['limit'] }}
                            </span>
                        </div>
                        @if(!$quotaWidget['unlimited'])
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="bg-gradient-to-r from-blue-500 to-violet-500 h-2 rounded-full"
                                 style="width: {{ min(100, round((max($quotaWidget['value'],0) / max($quotaWidget['limit'],1)) * 100)) }}%"></div>
                        </div>
                        @endif
                        <div class="flex items-center justify-between text-sm pt-1">
                            <span class="text-slate-500">{{ $planExpiry ? 'Berlaku sampai' : 'Status' }}</span>
                            <span class="font-semibold text-slate-800">
                                {{ $planExpiry ? $planExpiry->translatedFormat('d F Y') : 'Aktif' }}
                            </span>
                        </div>
                    </div>
                    <a href="{{ route('profile.edit') }}"
                       class="mt-4 block text-center w-full bg-blue-50 text-blue-700 font-semibold text-sm py-2.5 rounded-xl hover:bg-blue-100 transition">
                        Kelola Paket
                    </a>
                </div>
                </div>

            </div>

            {{-- ============ TIPS HARI INI ============ --}}
            @php
                $tips = [
                    'Gunakan materi pembelajaran sebagai referensi agar AI menghasilkan soal yang lebih akurat dan sesuai kebutuhan Anda.',
                    'Tentukan taksonomi Bloom (C1-C6) sebelum generate supaya level kognitif soal lebih terarah.',
                    'Campurkan pilihan ganda dan essay dalam satu bank soal untuk mengukur pemahaman siswa secara lebih menyeluruh.',
                    'Cek kembali soal hasil AI sebelum diekspor - Anda tetap pemegang kendali kualitas akhir.',
                ];
                $todayTip = $tips[now()->dayOfYear % count($tips)];
            @endphp
            <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.6 10.8c.5.4.8 1 .8 1.7v.5h5.6v-.5c0-.6.3-1.3.8-1.7A6 6 0 0 0 12 3Z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-amber-900">Tips Hari Ini</p>
                        <p class="text-xs text-amber-700 max-w-xl">{{ $todayTip }}</p>
                    </div>
                </div>
                @if($canGenerate)
                <a href="{{ route('generate-soal') }}"
                   class="shrink-0 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                    Coba Sekarang
                </a>
                @endif
            </div>

        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const subjectLabels = @json($subjectStats->pluck('subject'));
    const subjectTotals = @json($subjectStats->pluck('total'));
    const subjectCtx = document.getElementById('subjectChart');
    const subjectPalette = ['#3B82F6', '#22C55E', '#F59E0B', '#8B5CF6', '#EF4444', '#06B6D4', '#EC4899', '#84CC16'];
    const subjectColors = subjectLabels.map((_, i) => subjectPalette[i % subjectPalette.length]);

    if (subjectCtx) {
        new Chart(subjectCtx, {
            type: 'doughnut',
            data: {
                labels: subjectLabels,
                datasets: [{
                    data: subjectTotals,
                    backgroundColor: subjectColors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { display: false } }
            }
        });
    }

    const difficultyCtx = document.getElementById('difficultyChart');

    if (difficultyCtx) {
        new Chart(difficultyCtx, {
            type: 'doughnut',
            data: {
                labels: ['Mudah', 'Sedang', 'Sulit'],
                datasets: [{
                    data: [{{ $easyCount }}, {{ $mediumCount }}, {{ $hardCount }}],
                    backgroundColor: ['#22C55E', '#F59E0B', '#EF4444'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: { legend: { display: false } }
            }
        });
    }

    const questionTypeCtx = document.getElementById('questionTypeChart');

    if (questionTypeCtx) {
        new Chart(questionTypeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pilihan Ganda', 'Essay'],
                datasets: [{
                    data: [{{ $totalMultipleChoice }}, {{ $totalEssay }}],
                    backgroundColor: ['#3B82F6', '#8B5CF6'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: { legend: { display: false } }
            }
        });
    }

    const monthlyActivityChart = document.getElementById('monthlyActivityChart');
    const monthlyLabels = @json($monthlyLabels);
    const monthlyTotals = @json($monthlyTotals);

    if (monthlyActivityChart) {
        new Chart(monthlyActivityChart, {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Generate Soal',
                    data: monthlyTotals,
                    backgroundColor: '#2563eb',
                    borderRadius: 8,
                    maxBarThickness: 42
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

});
</script>
</x-app-layout>