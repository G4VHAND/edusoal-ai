<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Dashboard Sekolah</h1>
                <p class="text-slate-500 text-sm mt-1">Ringkasan {{ $school->name }}</p>
            </div>
        </div>

        {{-- Stats Utama --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            @php
                $isUnlimited = $stats['quota_limit'] === -1;
                $quotaRemaining = $isUnlimited ? '∞' : max(0, $stats['quota_limit'] - $stats['quota_used']);
            @endphp

            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-slate-500">Total Guru</p>
                    <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M12 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-slate-900">{{ $stats['total_teachers'] }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-slate-500">Total Bank Soal</p>
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-slate-900">{{ $stats['total_questions'] }}</p>
                <p class="text-xs text-slate-400 mt-1">Dibuat oleh semua guru sekolah ini</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-slate-500">Quota Sisa Bulan Ini</p>
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 8v4l3 3"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-slate-900">{{ $quotaRemaining }}</p>
                <p class="text-xs text-slate-400 mt-1">
                    dari {{ $isUnlimited ? 'unlimited' : $stats['quota_limit'] }} · dipakai bersama semua guru
                </p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-slate-500">Paket Aktif</p>
                    <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 0 0 1.946-.806 3.42 3.42 0 0 1 4.438 0 3.42 3.42 0 0 0 1.946.806 3.42 3.42 0 0 1 3.138 3.138 3.42 3.42 0 0 0 .806 1.946 3.42 3.42 0 0 1 0 4.438"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-slate-900">{{ $plan?->name ?? 'Trial' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            {{-- Info Paket Sekolah Saya --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h2 class="font-bold text-slate-900 mb-4">Info Paket Sekolah Saya</h2>

                @if($plan)
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Nama Paket</span>
                            <span class="font-semibold text-slate-800">{{ $plan->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Harga</span>
                            <span class="font-semibold text-slate-800">{{ $plan->formattedPriceMonthly() }}/bulan</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Max Guru</span>
                            <span class="font-semibold text-slate-800">{{ $plan->max_teachers === -1 ? '∞' : $plan->max_teachers }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Quota Generate/Bulan</span>
                            <span class="font-semibold text-slate-800">{{ $plan->quota_per_month === -1 ? '∞' : $plan->quota_per_month }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Upload Gambar</span>
                            <span>{{ $plan->allow_image_upload ? '✅' : '❌' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Export Word</span>
                            <span>{{ $plan->allow_export_word ? '✅' : '❌' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Export PDF</span>
                            <span>{{ $plan->allow_export_pdf ? '✅' : '❌' }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-slate-400 text-sm">Belum ada paket aktif. Hubungi admin platform untuk berlangganan.</p>
                @endif
            </div>

            {{-- Guru Terbaru --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-slate-900">Guru di Sekolah Saya</h2>
                    <a href="{{ route('admin.teachers.index') }}" class="text-sm text-blue-600 hover:underline">
                        Lihat semua →
                    </a>
                </div>

                @if($recentTeachers->isEmpty())
                    <div class="text-center py-8 text-slate-400">
                        <p class="text-3xl mb-2">👩‍🏫</p>
                        <p class="text-sm">Belum ada guru terdaftar.</p>
                        <a href="{{ route('admin.teachers.index') }}" class="mt-2 inline-block text-sm text-blue-600 hover:underline">
                            Tambah guru →
                        </a>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($recentTeachers as $teacher)
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2 last:border-0">
                            <div>
                                <p class="font-medium text-slate-800 text-sm">{{ $teacher->name }}</p>
                                <p class="text-xs text-slate-400">{{ $teacher->email }}</p>
                            </div>
                            <span class="text-xs text-slate-400">{{ $teacher->created_at->format('d M Y') }}</span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>
