<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-3xl font-bold text-slate-900">Selamat datang kembali</h2>
        <p class="text-slate-500 mt-2">Masuk ke akun EduSoal AI Anda</p>
    </div>

    {{-- Session Status --}}
    @if (session('status'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- Error global --}}
    @if ($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">
                Email
            </label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="guru@sekolah.sch.id"
                class="w-full px-4 py-3 rounded-xl border text-slate-900 placeholder-slate-400
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                       transition {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-slate-300' }}">
        </div>

        {{-- Password --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-semibold text-slate-700">
                    Password
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                        Lupa password?
                    </a>
                @endif
            </div>
            <div class="relative">
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="w-full px-4 py-3 rounded-xl border text-slate-900 placeholder-slate-400
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                           transition {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-slate-300' }}">
                <button type="button"
                        onclick="togglePassword('password', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Remember Me --}}
        <div class="flex items-center gap-2">
            <input
                id="remember_me"
                type="checkbox"
                name="remember"
                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            <label for="remember_me" class="text-sm text-slate-600">
                Ingat saya selama 30 hari
            </label>
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold
                       py-3 px-4 rounded-xl transition duration-150 shadow-sm shadow-blue-200">
            Masuk ke Akun
        </button>

        {{-- Register link --}}
        <p class="text-center text-sm text-slate-500">
            Belum punya akun?
            <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 font-semibold">
                Daftar sekarang
            </a>
        </p>
    </form>

    <script>
        function togglePassword(id, btn) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>

</x-guest-layout>