<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full px-4 lg:px-6 py-6">

            {{-- Hero Section (compact) --}}
            <div class="relative w-full overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-700 p-6 shadow-lg mb-6">
                <div class="relative z-10 max-w-3xl">
                    <div class="inline-flex items-center gap-1.5 bg-white/15 text-white px-3 py-1.5 rounded-full text-xs font-semibold mb-3">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 3v18M3 12h18"/>
                        </svg>
                        EduSoal AI Platform
                    </div>

                    <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">
                        Selamat Datang, {{ Auth::user()->name }}
                    </h1>

                    <p class="text-blue-100 text-sm mb-4">
                        Buat bank soal pilihan ganda dan essay secara lebih cepat dengan bantuan AI.
                    </p>

                    <div class="flex flex-wrap gap-3">
                        @if(auth()->user()->isTeacher() || auth()->user()->isIndividual())
                        <a href="{{ route('generate-soal') }}"
                           class="inline-flex items-center gap-1.5 bg-white text-blue-700 font-semibold px-4 py-2 text-sm rounded-lg shadow hover:bg-slate-100 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Generate Soal
                        </a>

                        <a href="{{ route('bank-soal') }}"
                           class="inline-flex items-center gap-1.5 bg-blue-500 text-white font-semibold px-4 py-2 text-sm rounded-lg hover:bg-blue-400 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>
                            </svg>
                            Bank Soal
                        </a>
                        @else
                        <a href="{{ route('admin.bank-soal.index') }}"
                           class="inline-flex items-center gap-1.5 bg-white text-blue-700 font-semibold px-4 py-2 text-sm rounded-lg shadow hover:bg-slate-100 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>
                            </svg>
                            Bank Soal Sekolah
                        </a>

                        <a href="{{ route('admin.teachers.index') }}"
                           class="inline-flex items-center gap-1.5 bg-blue-500 text-white font-semibold px-4 py-2 text-sm rounded-lg hover:bg-blue-400 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                            </svg>
                            Manajemen Guru
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Filter Periode --}}
            <div class="flex flex-wrap gap-2 mb-4">
                <a href="{{ route('dashboard', ['period' => 'all']) }}"
                class="px-3.5 py-1.5 rounded-lg text-xs font-medium
                {{ $period == 'all' ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-600' }}">
                    Semua Data
                </a>

                <a href="{{ route('dashboard', ['period' => '7days']) }}"
                class="px-3.5 py-1.5 rounded-lg text-xs font-medium
                {{ $period == '7days' ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-600' }}">
                    7 Hari
                </a>

                <a href="{{ route('dashboard', ['period' => '30days']) }}"
                class="px-3.5 py-1.5 rounded-lg text-xs font-medium
                {{ $period == '30days' ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-600' }}">
                    30 Hari
                </a>

                <a href="{{ route('dashboard', ['period' => 'year']) }}"
                class="px-3.5 py-1.5 rounded-lg text-xs font-medium
                {{ $period == 'year' ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-600' }}">
                    Tahun Ini
                </a>
            </div>

            {{-- Widget Ringkas --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                    <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500 mb-1">Total Bank Soal</p>
                    <p class="text-3xl font-bold text-slate-900">{{ $totalQuestionSets }}</p>
                    <p class="text-xs text-emerald-600 font-medium mt-1">Data aktif</p>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.32 2.577a49.255 49.255 0 0 1 11.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 0 1-1.085.67L12 18.089l-7.165 3.583A.75.75 0 0 1 3.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93Z" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500 mb-1">Total Pertanyaan</p>
                    <p class="text-3xl font-bold text-slate-900">{{ $totalQuestions }}</p>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                    <div class="w-11 h-11 rounded-xl bg-violet-50 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500 mb-1">Generate AI</p>
                    <p class="text-3xl font-bold text-slate-900">{{ $aiGeneratedCount }}</p>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                    <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500 mb-1">{{ $quotaWidget['label'] }}</p>
                    <p class="text-3xl font-bold text-slate-900">
                        {{ $quotaWidget['unlimited'] ? '∞' : $quotaWidget['value'] }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">
                        {{ $quotaWidget['unlimited'] ? 'Unlimited generate' : 'sisa dari '.$quotaWidget['limit'].' generate' }}
                    </p>
                    @if($quotaWidget['pooled'])
                    <p class="text-xs text-slate-400">Dipakai bersama semua guru di sekolah Anda.</p>
                    @endif
                    <div class="border-t border-slate-100 my-3"></div>
                    <p class="text-xs text-slate-400">Paket: {{ $quotaWidget['plan_name'] }}</p>
                </div>
            </div>

            {{-- Header Dashboard --}}
            <div class="mb-4">
                <h2 class="text-lg font-bold text-slate-900">
                    Dashboard Analytics
                </h2>
                <p class="text-sm text-slate-500">
                    Statistik penggunaan dan performa bank soal Anda.
                </p>
            </div>

            {{-- Insight Ringkas + Ringkasan Sistem --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">

                <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl p-4 shadow">
                    <p class="text-xs text-blue-100">
                        Total Soal Dibuat
                    </p>

                    <h3 class="text-2xl font-bold mt-1">
                        {{ $totalQuestions }}
                    </h3>

                    <p class="text-xs text-blue-100 mt-1.5 leading-snug">
                        Jumlah seluruh soal yang berhasil digenerate.
                    </p>
                </div>

                <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl p-4 shadow">
                    <p class="text-xs text-green-100">
                        Mata Pelajaran Terbanyak
                    </p>

                    <h3 class="text-2xl font-bold mt-1">
                        {{ $subjectStats->first()->subject ?? '-' }}
                    </h3>

                    <p class="text-xs text-green-100 mt-1.5 leading-snug">
                        Paling banyak dibuat dalam bank soal.
                    </p>
                </div>

                <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-xl p-4 shadow">
                    <p class="text-xs text-orange-100">
                        Tingkat Kesulitan Dominan
                    </p>

                    <h3 class="text-2xl font-bold mt-1">
                        @if($mediumCount >= $easyCount && $mediumCount >= $hardCount)
                            Sedang
                        @elseif($easyCount >= $hardCount)
                            Mudah
                        @else
                            Sulit
                        @endif
                    </h3>

                    <p class="text-xs text-orange-100 mt-1.5 leading-snug">
                        Tingkat kesulitan yang paling banyak digunakan.
                    </p>
                </div>

                {{-- Ringkasan Sistem --}}
                <div class="bg-white rounded-xl p-4 border border-slate-200">
                    <h3 class="font-bold text-sm text-slate-900 mb-3">
                        Ringkasan Sistem
                    </h3>

                    @php
                        $currentUser = auth()->user();
                        $remaining   = $currentUser->remainingQuota();
                        $limit       = $currentUser->quotaLimit();
                        $isUnlimited = ($limit === -1);
                        $displayRemaining = $isUnlimited ? 'Unlimited' : $remaining;
                    @endphp

                    <div class="mb-3">
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-slate-600">AI Generated</span>
                            <span class="font-semibold text-slate-800">{{ $aiGeneratedCount }}</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div
                                class="bg-blue-600 h-2 rounded-full"
                                style="width: {{ ($aiGeneratedCount / max($totalQuestionSets,1))*100 }}%">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-600">Total Bank Soal</span>
                        <span class="font-semibold text-slate-800">{{ number_format($totalQuestionSets) }}</span>
                    </div>

                    <div class="flex justify-between text-xs">
                        <span class="text-slate-600">{{ $currentUser->hasSchool() ? 'Sisa Quota Sekolah' : 'Sisa Quota' }}</span>
                        <span class="font-semibold text-slate-800">{{ $displayRemaining }}</span>
                    </div>
                </div>

            </div>

            {{-- Statistik Detail --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">

                <div class="bg-blue-50 rounded-lg p-3 border border-blue-100">
                    <p class="text-blue-600 text-xs font-medium">Pilihan Ganda</p>
                    <p class="text-2xl font-bold text-blue-700">{{ $totalMultipleChoice }}</p>
                </div>

                <div class="bg-violet-50 rounded-lg p-3 border border-violet-100">
                    <p class="text-violet-600 text-xs font-medium">Essay</p>
                    <p class="text-2xl font-bold text-violet-700">{{ $totalEssay }}</p>
                </div>

                <div class="bg-green-50 rounded-lg p-3 border border-green-100">
                    <p class="text-green-600 text-xs font-medium">Mudah</p>
                    <p class="text-2xl font-bold text-green-700">{{ $easyCount }}</p>
                </div>

                <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-100">
                    <p class="text-yellow-600 text-xs font-medium">Sedang</p>
                    <p class="text-2xl font-bold text-yellow-700">{{ $mediumCount }}</p>
                </div>

                <div class="bg-red-50 rounded-lg p-3 border border-red-100">
                    <p class="text-red-600 text-xs font-medium">Sulit</p>
                    <p class="text-2xl font-bold text-red-700">{{ $hardCount }}</p>
                </div>

            </div>

            {{-- Grafik Statistik (4 kolom, compact) --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">

                {{-- Mata Pelajaran --}}
                <div class="bg-white rounded-xl border border-slate-200 p-4 h-[340px] flex flex-col">
                    <div class="mb-2 shrink-0">
                        <h2 class="text-sm font-bold text-slate-900">
                            Distribusi Mata Pelajaran
                        </h2>
                        <p class="text-xs text-slate-500">
                            Sebaran bank soal berdasarkan mata pelajaran.
                        </p>
                    </div>

                    <div class="flex-1 min-h-0 relative">
                        <canvas id="subjectChart"></canvas>
                    </div>
                </div>

                {{-- Tingkat Kesulitan --}}
                <div class="bg-white rounded-xl border border-slate-200 p-4 h-[340px] flex flex-col">
                    <div class="mb-2 shrink-0">
                        <h2 class="text-sm font-bold text-slate-900">
                            Tingkat Kesulitan
                        </h2>
                        <p class="text-xs text-slate-500">
                            Distribusi tingkat kesulitan soal.
                        </p>
                    </div>

                    <div class="flex-1 min-h-0 relative">
                        <canvas id="difficultyChart"></canvas>
                    </div>
                </div>

                {{-- Jenis Soal --}}
                <div class="bg-white rounded-xl border border-slate-200 p-4 h-[340px] flex flex-col">
                    <div class="mb-2 shrink-0">
                        <h2 class="text-sm font-bold text-slate-900">
                            Jenis Soal
                        </h2>
                        <p class="text-xs text-slate-500">
                            Perbandingan pilihan ganda dan essay.
                        </p>
                    </div>

                    <div class="flex-1 min-h-0 relative">
                        <canvas id="questionTypeChart"></canvas>
                    </div>
                </div>

                {{-- Aktivitas Generate Soal --}}
                <div class="bg-white rounded-xl border border-slate-200 p-4 h-[340px] flex flex-col">
                    <div class="mb-2 shrink-0">
                        <h2 class="text-sm font-bold text-slate-900">
                            Aktivitas Generate Soal
                        </h2>
                        <p class="text-xs text-slate-500">
                            Jumlah bank soal yang dibuat setiap bulan.
                        </p>
                    </div>

                    <div class="flex-1 min-h-0 relative">
                        <canvas id="monthlyActivityChart"></canvas>
                    </div>
                </div>

            </div>

            {{-- Aktivitas Terbaru + Top 5 Mata Pelajaran --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                {{-- Aktivitas Terbaru --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">
                                Aktivitas Terbaru
                            </h2>
                            <p class="text-slate-500 text-xs">
                                Daftar bank soal terakhir yang dibuat.
                            </p>
                        </div>

                        <a href="{{ route('bank-soal') }}"
                           class="text-blue-600 text-sm font-semibold hover:text-blue-800">
                            Lihat Semua
                        </a>
                    </div>

                    @forelse($latestQuestionSets as $item)
                        <div class="border border-slate-200 rounded-lg p-3 mb-3 hover:bg-slate-50 transition">
                            <div class="flex flex-wrap justify-between items-center gap-3">
                                <div>
                                    <h3 class="font-bold text-sm text-slate-900">
                                        {{ $item['title'] }}
                                    </h3>

                                    <p class="text-slate-500 text-xs">
                                        {{ $item['subject'] }}
                                        &bull; {{ $item['grade'] }}
                                        &bull; {{ ucfirst($item['difficulty']) }}
                                    </p>
                                </div>

                                <a href="{{ route('bank-soal.show', $item['id']) }}"
                                   class="inline-flex items-center gap-1 text-blue-600 text-sm font-semibold">
                                    Detail
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M9 18l6-6-6-6"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <h3 class="text-base font-semibold text-slate-900 mb-2">
                                Belum ada bank soal
                            </h3>

                            <p class="text-slate-500 text-sm mb-4">
                                Mulai buat bank soal pertama Anda melalui halaman Generate Soal.
                            </p>

                            <a href="{{ route('generate-soal') }}"
                               class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 text-sm rounded-lg font-semibold hover:bg-blue-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                Generate Soal
                            </a>
                        </div>
                    @endforelse
                </div>

                {{-- Top 5 Mata Pelajaran --}}
                <div class="bg-white rounded-xl border border-slate-200 p-5">

                    <div class="mb-4">
                        <h2 class="text-base font-bold text-slate-900">
                            Top 5 Mata Pelajaran
                        </h2>

                        <p class="text-slate-500 text-xs">
                            Mata pelajaran yang paling sering digunakan.
                        </p>
                    </div>

                    @forelse($topSubjects as $index => $subject)

                        <div class="mb-4">

                            <div class="flex justify-between items-center mb-1.5">

                                <div class="flex items-center gap-2">

                                    <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">
                                        {{ $index + 1 }}
                                    </span>

                                    <span class="font-medium text-sm text-slate-800">
                                        {{ $subject->subject }}
                                    </span>

                                </div>

                                <span class="font-semibold text-sm text-slate-600">
                                    {{ $subject->total }}
                                </span>

                            </div>

                            <div class="w-full bg-slate-200 rounded-full h-2">
                                <div
                                    class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full"
                                    style="width: {{ ($subject->total / max($topSubjects->max('total'),1)) * 100 }}%">
                                </div>
                            </div>

                        </div>

                    @empty

                        <div class="text-center py-8 text-slate-500 text-sm">
                            Belum ada data.
                        </div>

                    @endforelse

                </div>

            </div>

        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Chart Distribusi Mata Pelajaran
    const subjectLabels = @json($subjectStats->pluck('subject'));
    const subjectTotals = @json($subjectStats->pluck('total'));
    const subjectCtx = document.getElementById('subjectChart');
    const subjectPalette = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EF4444', '#06B6D4', '#EC4899', '#84CC16'];
    const subjectColors = subjectLabels.map((_, i) => subjectPalette[i % subjectPalette.length]);

    if (subjectCtx) {
        new Chart(subjectCtx, {
            type: 'bar',
            data: {
                labels: subjectLabels,
                datasets: [{
                    label: 'Jumlah Bank Soal',
                    data: subjectTotals,
                    backgroundColor: subjectColors,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    // Chart Tingkat Kesulitan
    const difficultyCtx = document.getElementById('difficultyChart');

    if (difficultyCtx) {
        new Chart(difficultyCtx, {
            type: 'doughnut',
            data: {
                labels: ['Mudah', 'Sedang', 'Sulit'],
                datasets: [{
                    data: [{{ $easyCount }}, {{ $mediumCount }}, {{ $hardCount }}],
                    backgroundColor: ['#22C55E', '#EAB308', '#EF4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }
            }
        });
    }

    // Chart Jenis Soal
    const questionTypeCtx = document.getElementById('questionTypeChart');

    if (questionTypeCtx) {
        new Chart(questionTypeCtx, {
            type: 'pie',
            data: {
                labels: ['Pilihan Ganda', 'Essay'],
                datasets: [{
                    data: [{{ $totalMultipleChoice }}, {{ $totalEssay }}],
                    backgroundColor: ['#3B82F6', '#8B5CF6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                radius: '85%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }
            }
        });
    }

    // Chart Aktivitas Bulanan
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
                    borderRadius: 6,
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

});
</script>
</x-app-layout>