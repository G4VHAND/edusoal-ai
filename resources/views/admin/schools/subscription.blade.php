<x-app-layout>
    <div class="p-6 max-w-2xl mx-auto">
        <a href="{{ route('admin.schools.show', $school) }}"
           class="text-blue-600 text-sm mb-4 inline-block">← Kembali ke Detail Sekolah</a>

        <h1 class="text-2xl font-bold text-slate-900 mb-2">Perpanjang / Upgrade Subscription</h1>
        <p class="text-slate-500 mb-6">{{ $school->name }}</p>

        {{-- Info subscription aktif --}}
        @if($activeSub)
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-6">
            <p class="text-sm font-semibold text-slate-700 mb-2">Subscription Saat Ini</p>
            <div class="grid grid-cols-3 gap-3 text-sm">
                <div>
                    <p class="text-slate-500">Paket</p>
                    <p class="font-medium text-slate-800">{{ $activeSub->plan?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Status</p>
                    <p class="font-medium {{ $activeSub->isActive() ? 'text-green-600' : 'text-red-600' }}">
                        {{ ucfirst($activeSub->status) }}
                    </p>
                </div>
                <div>
                    <p class="text-slate-500">Berakhir</p>
                    <p class="font-medium {{ $activeSub->ends_at->isPast() ? 'text-red-600' : 'text-slate-800' }}">
                        {{ $activeSub->ends_at->format('d M Y') }}
                    </p>
                </div>
            </div>
        </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.schools.subscription.update', $school) }}"
              class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
            @csrf

            {{-- Pilih paket --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Paket Langganan</label>
                <div class="grid grid-cols-2 gap-3">
                    @foreach($plans as $plan)
                    <label class="cursor-pointer">
                        <input type="radio" name="plan_slug" value="{{ $plan->slug }}"
                               {{ old('plan_slug', $activeSub?->plan?->slug) == $plan->slug ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="border-2 border-slate-200 peer-checked:border-blue-500
                                    peer-checked:bg-blue-50 rounded-xl p-4 transition">
                            <p class="font-semibold text-slate-800">{{ $plan->name }}</p>
                            <p class="text-sm text-blue-600 mt-1">{{ $plan->formattedPriceMonthly() }}/bulan</p>
                            <div class="text-xs text-slate-500 mt-2 space-y-0.5">
                                <p>{{ $plan->quota_per_month === -1 ? 'Unlimited' : $plan->quota_per_month }} generate/bln</p>
                                <p>Max {{ $plan->max_teachers === -1 ? 'unlimited' : $plan->max_teachers }} guru</p>
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                {{-- Siklus billing --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Siklus Billing</label>
                    <select name="billing_cycle" class="w-full border border-slate-300 rounded-xl p-3">
                        <option value="monthly" {{ old('billing_cycle', 'monthly') == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                        <option value="yearly"  {{ old('billing_cycle') == 'yearly' ? 'selected' : '' }}>Tahunan</option>
                    </select>
                </div>

                {{-- Durasi --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Durasi (bulan)</label>
                    <input type="number" name="duration_months" min="1" max="24"
                           value="{{ old('duration_months', 1) }}"
                           class="w-full border border-slate-300 rounded-xl p-3">
                </div>

                {{-- Jumlah dibayar --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Jumlah Dibayar (Rp)</label>
                    <input type="number" name="amount_paid" min="0"
                           value="{{ old('amount_paid', 0) }}"
                           placeholder="0"
                           class="w-full border border-slate-300 rounded-xl p-3">
                </div>

                {{-- Metode pembayaran --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Metode Pembayaran</label>
                    <select name="payment_method" class="w-full border border-slate-300 rounded-xl p-3">
                        <option value="">-- Pilih --</option>
                        <option value="transfer_bca">Transfer BCA</option>
                        <option value="transfer_mandiri">Transfer Mandiri</option>
                        <option value="transfer_bri">Transfer BRI</option>
                        <option value="midtrans">Midtrans</option>
                        <option value="cash">Tunai</option>
                        <option value="gratis">Gratis / Trial</option>
                    </select>
                </div>
            </div>

            {{-- Referensi pembayaran --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">
                    Referensi Pembayaran
                    <span class="text-slate-400 font-normal">(nomor transfer, invoice, dll)</span>
                </label>
                <input type="text" name="payment_ref" value="{{ old('payment_ref') }}"
                       placeholder="INV-2026-001"
                       class="w-full border border-slate-300 rounded-xl p-3">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition">
                    Simpan Subscription
                </button>
                <a href="{{ route('admin.schools.show', $school) }}"
                   class="px-6 py-3 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-app-layout>