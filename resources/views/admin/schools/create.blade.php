<x-app-layout>
    <div class="p-6 max-w-3xl mx-auto">
        <h1 class="text-2xl font-bold text-slate-900 mb-6">Daftarkan Sekolah Baru</h1>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.schools.store') }}"
              class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
            @csrf

            <p class="font-semibold text-slate-700 border-b pb-3">Informasi Sekolah</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Sekolah</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full border border-slate-300 rounded-xl p-3" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email Sekolah</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full border border-slate-300 rounded-xl p-3" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nomor Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full border border-slate-300 rounded-xl p-3">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Jenjang</label>
                    <select name="level" class="w-full border border-slate-300 rounded-xl p-3">
                        @foreach(['sd'=>'SD','smp'=>'SMP','sma'=>'SMA','smk'=>'SMK','mixed'=>'Campuran'] as $val=>$label)
                            <option value="{{ $val }}" {{ old('level') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kota</label>
                    <input type="text" name="city" value="{{ old('city') }}"
                           class="w-full border border-slate-300 rounded-xl p-3">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Provinsi</label>
                    <input type="text" name="province" value="{{ old('province') }}"
                           class="w-full border border-slate-300 rounded-xl p-3">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Alamat</label>
                <textarea name="address" rows="2"
                          class="w-full border border-slate-300 rounded-xl p-3">{{ old('address') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Paket Langganan</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($plans as $plan)
                    <label class="cursor-pointer">
                        <input type="radio" name="plan_slug" value="{{ $plan->slug }}"
                               {{ old('plan_slug', 'free') == $plan->slug ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="border-2 border-slate-200 peer-checked:border-blue-500
                                    peer-checked:bg-blue-50 rounded-xl p-3 text-center transition">
                            <p class="font-semibold text-slate-800">{{ $plan->name }}</p>
                            <p class="text-sm text-blue-600">{{ $plan->formattedPriceMonthly() }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ $plan->quota_per_month === -1 ? 'Unlimited' : $plan->quota_per_month }} generate/bln</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            <p class="font-semibold text-slate-700 border-b pb-3 mt-2">Akun Admin Sekolah</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Admin</label>
                    <input type="text" name="admin_name" value="{{ old('admin_name') }}"
                           class="w-full border border-slate-300 rounded-xl p-3" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email Admin</label>
                    <input type="email" name="admin_email" value="{{ old('admin_email') }}"
                           class="w-full border border-slate-300 rounded-xl p-3" required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password Admin</label>
                    <input type="password" name="admin_password"
                           class="w-full border border-slate-300 rounded-xl p-3" required>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold">
                    Daftarkan Sekolah
                </button>
                <a href="{{ route('admin.schools.index') }}"
                   class="px-6 py-3 rounded-xl border border-slate-300 text-slate-700">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
