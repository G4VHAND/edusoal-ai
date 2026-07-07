<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full px-6 lg:px-8 py-8 max-w-4xl mx-auto">

            {{-- Header Profil --}}
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-bold text-2xl flex-shrink-0">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ Auth::user()->name }}</h1>
                    <p class="text-slate-500 text-sm">{{ Auth::user()->email }}</p>
                    <span class="inline-block mt-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-50 text-blue-600">
                        {{ match(Auth::user()->role) {
                            'super_admin' => 'Super Admin',
                            'school_admin' => 'Admin Sekolah',
                            'teacher' => 'Guru',
                            'individual' => 'Individual',
                            default => ucfirst(Auth::user()->role),
                        } }}
                    </span>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200">
                    <div class="max-w-xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200">
                    <div class="max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-red-100">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
