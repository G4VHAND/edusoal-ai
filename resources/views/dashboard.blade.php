<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full px-6 lg:px-8 py-8">

            {{-- Hero Section --}}
            <div class="relative w-full overflow-hidden rounded-3xl bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-700 p-8 shadow-xl mb-8">
                <div class="relative z-10 max-w-3xl">
                    <div class="inline-flex items-center gap-2 bg-white/15 text-white px-4 py-2 rounded-full text-sm font-semibold mb-5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 3v18M3 12h18"/>
                        </svg>
                        EduSoal AI Platform
                    </div>

                    <h1 class="text-4xl font-bold text-white mb-3">
                        Selamat Datang, {{ Auth::user()->name }}
                    </h1>

                    <p class="text-blue-100 text-lg mb-6">
                        Buat bank soal pilihan ganda dan essay secara lebih cepat dengan bantuan AI.
                    </p>

                    <div class="flex flex-wrap gap-4">
                        @if(auth()->user()->isTeacher() || auth()->user()->isIndividual())
                        <a href="{{ route('generate-soal') }}"
                           class="inline-flex items-center gap-2 bg-white text-blue-700 font-semibold px-6 py-3 rounded-xl shadow hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Generate Soal
                        </a>

                        <a href="{{ route('bank-soal') }}"
                           class="inline-flex items-center gap-2 bg-blue-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-blue-400 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>
                            </svg>
                            Bank Soal
                        </a>
                        @else
                        <a href="{{ route('admin.bank-soal.index') }}"
                           class="inline-flex items-center gap-2 bg-white text-blue-700 font-semibold px-6 py-3 rounded-xl shadow hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>
                            </svg>
                            Bank Soal Sekolah
                        </a>

                        <a href="{{ route('admin.teachers.index') }}"
                           class="inline-flex items-center gap-2 bg-blue-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-blue-400 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                            </svg>
                            Manajemen Guru
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Statistik Utama --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                    <div class="bg-blue-100 text-blue-600 w-12 h-12 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                            <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>
                        </svg>
                    </div>

                    <p class="text-slate-500 text-sm mb-2">Total Bank Soal</p>
                    <h2 class="text-4xl font-bold">
                        {{ number_format($totalQuestionSets) }}
                    </h2>

                    <span class="text-xs text-green-600 font-semibold">
                        Data aktif
                    </span>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                    <div class="bg-green-100 text-green-600 w-12 h-12 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M9 11h6M9 15h6"/>
                            <path d="M7 3h10a2 2 0 0 1 2 2v16l-4-2-3 2-3-2-4 2V5a2 2 0 0 1 2-2z"/>
                        </svg>
                    </div>

                    <p class="text-slate-500 text-sm mb-2">Total Pertanyaan</p>
                    <h2 class="text-4xl font-bold text-slate-900">{{ $totalQuestions }}</h2>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                    <div class="bg-violet-100 text-violet-600 w-12 h-12 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                        </svg>
                    </div>

                    <p class="text-slate-500 text-sm mb-2">Generate AI</p>
                    <h2 class="text-4xl font-bold text-slate-900">{{ $aiGeneratedCount }}</h2>
                </div>

                {{-- Quota Card --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                    @php
                        $remaining = auth()->user()->remainingQuota();
                        $plan      = auth()->user()->subscriptionPlan;
                        $limit     = $plan ? $plan->quota_per_month : 10;
                        $used      = auth()->user()->quota_used_this_month;
                        $isUnlimited = ($limit === -1);
                        $pct       = (!$isUnlimited && $limit > 0) ? min(100, round(($used / $limit) * 100)) : 0;
                        $displayRemaining = $isUnlimited ? 'Unlimited' : $remaining;
                    @endphp
                    <div class="bg-amber-100 text-amber-600 w-12 h-12 rounded-xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 8v4l3 3"/>
                        </svg>
                    </div>
                    <p class="text-slate-500 text-sm mb-1">Quota Bulan Ini</p>
                    <h2 class="text-4xl font-bold text-slate-900">
                        {{ $displayRemaining }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">sisa dari {{ $isUnlimited ? 'unlimited' : $limit }} generate</p>
                    @if(!$isUnlimited)
                    <div class="mt-2 bg-slate-100 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $pct > 80 ? 'bg-red-400' : 'bg-amber-400' }}"
                             style="width: {{ $pct }}%"></div>
                    </div>
                    @endif
                    <p class="text-xs text-slate-400 mt-1">
                        Paket: {{ $plan?->name ?? 'Free' }}
                    </p>
                </div>

            </div>

            {{-- Statistik Detail --}}
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">

                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <p class="text-blue-600 text-sm font-medium">Pilihan Ganda</p>
                    <p class="text-3xl font-bold text-blue-700">{{ $totalMultipleChoice }}</p>
                </div>

                <div class="bg-violet-50 rounded-xl p-4 border border-violet-100">
                    <p class="text-violet-600 text-sm font-medium">Essay</p>
                    <p class="text-3xl font-bold text-violet-700">{{ $totalEssay }}</p>
                </div>

                <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                    <p class="text-green-600 text-sm font-medium">Mudah</p>
                    <p class="text-3xl font-bold text-green-700">{{ $easyCount }}</p>
                </div>

                <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-100">
                    <p class="text-yellow-600 text-sm font-medium">Sedang</p>
                    <p class="text-3xl font-bold text-yellow-700">{{ $mediumCount }}</p>
                </div>

                <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                    <p class="text-red-600 text-sm font-medium">Sulit</p>
                    <p class="text-3xl font-bold text-red-700">{{ $hardCount }}</p>
                </div>

            </div>

            {{-- Header Dashboard --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="flex flex-wrap gap-2 mb-6">
                        <a href="{{ route('dashboard', ['period' => 'all']) }}"
                        class="px-4 py-2 rounded-xl text-sm font-medium
                        {{ $period == 'all' ? 'bg-blue-600 text-white' : 'bg-white border' }}">
                            Semua Data
                        </a>

                        <a href="{{ route('dashboard', ['period' => '7days']) }}"
                        class="px-4 py-2 rounded-xl text-sm font-medium
                        {{ $period == '7days' ? 'bg-blue-600 text-white' : 'bg-white border' }}">
                            7 Hari
                        </a>

                        <a href="{{ route('dashboard', ['period' => '30days']) }}"
                        class="px-4 py-2 rounded-xl text-sm font-medium
                        {{ $period == '30days' ? 'bg-blue-600 text-white' : 'bg-white border' }}">
                            30 Hari
                        </a>

                        <a href="{{ route('dashboard', ['period' => 'year']) }}"
                        class="px-4 py-2 rounded-xl text-sm font-medium
                        {{ $period == 'year' ? 'bg-blue-600 text-white' : 'bg-white border' }}">
                            Tahun Ini
                        </a>

                    </div>
                    
                    <h2 class="text-2xl font-bold text-slate-900">
                        Dashboard Analytics
                    </h2>
                    <p class="text-slate-500">
                        Statistik penggunaan dan performa bank soal Anda.
                    </p>
                </div>

                <div class="hidden md:flex items-center gap-2 bg-blue-50 text-blue-700 px-4 py-2 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 3v18h18"/>
                        <path d="M8 14l3-3 2 2 5-5"/>
                    </svg>
                    Analytics
                </div>
            </div>

            {{-- Insight Ringkas --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

                <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-blue-100">
                        Total Soal Dibuat
                    </p>

                    <h3 class="text-2xl font-bold mt-2">
                        {{ $totalQuestions }}
                    </h3>

                    <p class="text-sm text-blue-100 mt-2">
                        Jumlah seluruh soal yang berhasil digenerate.
                    </p>
                </div>

                <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-green-100">
                        Mata Pelajaran Terbanyak
                    </p>

                    <h3 class="text-2xl font-bold mt-2">
                        {{ $subjectStats->first()->subject ?? '-' }}
                    </h3>

                    <p class="text-sm text-green-100 mt-2">
                        Paling banyak dibuat dalam bank soal.
                    </p>
                </div>

                <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-2xl p-5 shadow">
                    <p class="text-sm text-orange-100">
                        Tingkat Kesulitan Dominan
                    </p>

                    <h3 class="text-2xl font-bold mt-2">
                        @if($mediumCount >= $easyCount && $mediumCount >= $hardCount)
                            Sedang
                        @elseif($easyCount >= $hardCount)
                            Mudah
                        @else
                            Sulit
                        @endif
                    </h3>

                    <p class="text-sm text-orange-100 mt-2">
                        Tingkat kesulitan yang paling banyak digunakan.
                    </p>
                </div>

            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200 mb-8">
            <h3 class="font-bold text-slate-900 mb-4">
                Ringkasan Sistem
            </h3>

            <div class="grid md:grid-cols-3 gap-4">

                <div>
                    <div class="flex justify-between mb-2">
                        <span>AI Generated</span>
                        <span>{{ $aiGeneratedCount }}</span>
                    </div>

                    <div class="w-full bg-slate-200 rounded-full h-3">
                        <div
                            class="bg-blue-600 h-3 rounded-full"
                            style="width: {{ ($aiGeneratedCount / max($totalQuestionSets,1))*100 }}%">
                        </div>
                    </div>
                </div>

            </div>
        </div>

            {{-- Grafik Statistik --}}
           <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

                {{-- Mata Pelajaran --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-6 h-[420px]">
                    <div class="mb-4">
                        <h2 class="text-lg font-bold text-slate-900">
                            Distribusi Mata Pelajaran
                        </h2>
                        <p class="text-sm text-slate-500">
                            Sebaran bank soal berdasarkan mata pelajaran.
                        </p>
                    </div>

                    <div class="h-80">
                        <canvas id="subjectChart"></canvas>
                    </div>
                </div>


                {{-- Tingkat Kesulitan --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-6 h-[420px]">
                    <div class="mb-4">
                        <h2 class="text-lg font-bold text-slate-900">
                            Tingkat Kesulitan
                        </h2>
                        <p class="text-sm text-slate-500">
                            Distribusi tingkat kesulitan soal.
                        </p>
                    </div>

                    <div class="h-80">
                        <canvas id="difficultyChart"></canvas>
                    </div>
                </div>

                {{-- Jenis Soal --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-6 h-[420px]">
                    <div class="mb-4">
                        <h2 class="text-lg font-bold text-slate-900">
                            Jenis Soal
                        </h2>
                        <p class="text-sm text-slate-500">
                            Perbandingan pilihan ganda dan essay.
                        </p>
                    </div>

                    <div class="h-80">
                        <canvas id="questionTypeChart"></canvas>
                    </div>
                </div>

            </div>

            {{-- Aktivitas Generate Soal + Top Mata Pelajaran --}}
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">

                {{-- Aktivitas Generate Soal --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-6">

                    <div class="mb-4">
                        <h2 class="text-xl font-bold text-slate-900">
                            Aktivitas Generate Soal
                        </h2>

                        <p class="text-slate-500 text-sm">
                            Jumlah bank soal yang dibuat setiap bulan.
                        </p>
                    </div>

                    <div class="h-80">
                        <canvas id="monthlyActivityChart"></canvas>
                    </div>

                </div>

                {{-- Top 5 Mata Pelajaran --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-6">

                    <div class="mb-4">
                        <h2 class="text-xl font-bold text-slate-900">
                            Top 5 Mata Pelajaran
                        </h2>

                        <p class="text-slate-500 text-sm">
                            Mata pelajaran yang paling sering digunakan.
                        </p>
                    </div>

                    @forelse($topSubjects as $index => $subject)

                        <div class="mb-5">

                            <div class="flex justify-between items-center mb-2">

                                <div class="flex items-center gap-2">

                                    <span class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 text-sm font-bold flex items-center justify-center">
                                        {{ $index + 1 }}
                                    </span>

                                    <span class="font-medium text-slate-800">
                                        {{ $subject->subject }}
                                    </span>

                                </div>

                                <span class="font-semibold text-slate-600">
                                    {{ $subject->total }}
                                </span>

                            </div>

                            <div class="w-full bg-slate-200 rounded-full h-3">
                                <div
                                    class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full"
                                    style="width: {{ ($subject->total / max($topSubjects->max('total'),1)) * 100 }}%">
                                </div>
                            </div>

                        </div>

                    @empty

                        <div class="text-center py-10 text-slate-500">
                            Belum ada data.
                        </div>

                    @endforelse

                </div>

            </div>

            {{-- Aktivitas Terbaru --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">
                            Aktivitas Terbaru
                        </h2>
                        <p class="text-slate-500 text-sm">
                            Daftar bank soal terakhir yang dibuat.
                        </p>
                    </div>

                    <a href="{{ route('bank-soal') }}"
                       class="text-blue-600 font-semibold hover:text-blue-800">
                        Lihat Semua
                    </a>
                </div>

                @forelse($latestQuestionSets as $item)
                    <div class="border border-slate-200 rounded-xl p-4 mb-4 hover:bg-slate-50 transition">
                        <div class="flex flex-wrap justify-between items-center gap-4">
                            <div>
                                <h3 class="font-bold text-lg text-slate-900">
                                    {{ $item['title'] }}
                                </h3>

                                <p class="text-slate-500">
                                    {{ $item['subject'] }}
                                    &bull; {{ $item['grade'] }}
                                    &bull; {{ ucfirst($item['difficulty']) }}
                                </p>
                            </div>

                            <a href="{{ route('bank-soal.show', $item['id']) }}"
                               class="inline-flex items-center gap-2 text-blue-600 font-semibold">
                                Detail
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M9 18l6-6-6-6"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10">
                        <h3 class="text-xl font-semibold text-slate-900 mb-3">
                            Belum ada bank soal
                        </h3>

                        <p class="text-slate-500 mb-6">
                            Mulai buat bank soal pertama Anda melalui halaman Generate Soal.
                        </p>

                        <a href="{{ route('generate-soal') }}"
                           class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-3 rounded-xl font-semibold hover:bg-blue-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Generate Soal
                        </a>
                    </div>
                @endforelse
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

    if (subjectCtx) {
        new Chart(subjectCtx, {
            type: 'bar',
            data: {
                labels: subjectLabels,
                datasets: [{
                    label: 'Jumlah Bank Soal',
                    data: subjectTotals,
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EF4444'],
                    borderRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
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
                plugins: { legend: { position: 'bottom' } }
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
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // Chart Aktivitas Bulanan
    const monthlyActivityChart = document.getElementById('monthlyActivityChart');
    const monthlyLabels = @json($monthlyLabels);
    const monthlyTotals = @json($monthlyTotals);

    if (monthlyActivityChart) {
        new Chart(monthlyActivityChart, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Generate Soal',
                    data: monthlyTotals,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

});
</script>
</x-app-layout>
