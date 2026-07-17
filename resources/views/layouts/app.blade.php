<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>EduSoal AI</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-slate-50">
    <div class="min-h-screen flex">

        {{-- Sidebar Desktop --}}
        <aside class="hidden lg:flex lg:w-64 lg:flex-col bg-white border-r border-slate-200 fixed inset-y-0 z-40">

            <div class="h-16 flex items-center px-5 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-sm">
                        AI
                    </div>

                    <div>
                        <h1 class="font-bold text-slate-900 text-sm">
                            EduSoal AI
                        </h1>
                        <p class="text-xs text-slate-500">
                            Smart Question Generator
                        </p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1">

                @if(auth()->user()->isTeacher() || auth()->user()->isIndividual())
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-sm transition
                   {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 13h8V3H3v10zM13 21h8V3h-8v18zM3 21h8v-6H3v6z"/>
                    </svg>
                    Dashboard
                </a>
                @endif

                {{-- Generate Soal & Bank Soal — hanya untuk guru dan individual --}}
                @if(auth()->user()->isTeacher() || auth()->user()->isIndividual())
                <a href="{{ route('generate-soal') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-sm transition
                   {{ request()->routeIs('generate-soal*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Generate Soal
                </a>

                <a href="{{ route('bank-soal') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-sm transition
                   {{ request()->routeIs('bank-soal*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>
                    </svg>
                    Bank Soal
                </a>

                {{-- Template Dokumen — hanya individual, guru tidak perlu tahu
                     soal ini karena export mereka otomatis pakai template
                     default sekolah masing-masing --}}
                @if(auth()->user()->isIndividual())
                <a href="{{ route('templates.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-sm transition
                   {{ request()->routeIs('templates*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    Template Dokumen
                </a>
                @endif
                @endif

                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-sm transition
                   {{ request()->routeIs('profile.edit') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M20 21a8 8 0 1 0-16 0"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Profil
                </a>

                {{-- Menu Admin Panel — hanya untuk super_admin dan school_admin --}}
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin())
                <div class="pt-3 mt-1 border-t border-slate-200">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider px-4 mb-2">
                        Admin Panel
                    </p>

                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-sm transition
                       {{ request()->routeIs('admin.dashboard') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M3 13h8V3H3v10zM13 21h8V3h-8v18zM3 21h8v-6H3v6z"/>
                        </svg>
                        Dashboard Admin
                    </a>

                    @if(auth()->user()->isSuperAdmin())
                    <a href="{{ route('admin.schools.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-sm transition
                       {{ request()->routeIs('admin.schools*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        Sekolah
                    </a>

                    <a href="{{ route('admin.individuals.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-sm transition
                       {{ request()->routeIs('admin.individuals*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M20 21a8 8 0 1 0-16 0"/>
                        </svg>
                        User Individual
                    </a>
                    @endif

                    <a href="{{ route('admin.teachers.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-sm transition
                       {{ request()->routeIs('admin.teachers*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        Manajemen Guru
                    </a>

                    {{-- Bank Soal Sekolah --}}
                    <a href="{{ route('admin.bank-soal.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-sm transition
                       {{ request()->routeIs('admin.bank-soal*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                            <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/>
                        </svg>
                        Bank Soal Sekolah
                    </a>

                    @if(auth()->user()->isSchoolAdmin())
                    <a href="{{ route('admin.letterhead.edit') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-sm transition
                       {{ request()->routeIs('admin.letterhead*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M8 7h8M8 11h8M8 15h5"/>
                        </svg>
                        Kop Surat
                    </a>

                    <a href="{{ route('admin.ai-provider.edit') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-sm transition
                       {{ request()->routeIs('admin.ai-provider*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z"/>
                        </svg>
                        Provider AI
                    </a>

                    <a href="{{ route('templates.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg font-medium text-sm transition
                       {{ request()->routeIs('templates*') ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        Template Dokumen
                    </a>
                    @endif
                </div>
                @endif

            </nav>

            <div class="p-3 border-t border-slate-200">
                <div class="bg-slate-50 rounded-xl p-3 mb-3">
                    <p class="font-semibold text-slate-900">
                        {{ Auth::user()->name }}
                    </p>
                    <p class="text-sm text-slate-500 truncate">
                        {{ Auth::user()->email }}
                    </p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-red-600 hover:bg-red-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <path d="M10 17l5-5-5-5"/>
                            <path d="M15 12H3"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>

        </aside>

        {{-- Main Area --}}
        <div class="flex-1 lg:pl-64 flex flex-col min-h-screen">

            {{-- Topbar --}}
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-5 sticky top-0 z-30">

                <div>
                    <h2 class="font-bold text-slate-900 text-sm">
                        EduSoal AI
                    </h2>
                    <p class="text-xs text-slate-500">
                        Dashboard aplikasi pembuat soal berbasis AI
                    </p>
                </div>

                <a href="{{ route('profile.edit') }}"
                   class="hidden md:flex items-center gap-2.5 pl-2 pr-3.5 py-1.5 rounded-full border border-slate-200 hover:bg-slate-50 transition">
                    <span class="w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-bold flex items-center justify-center shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                    <span class="text-left leading-tight">
                        <span class="block text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</span>
                        <span class="block text-xs text-slate-500">Lihat Profil</span>
                    </span>
                </a>

            </header>

            {{-- Mobile Menu --}}
            <div class="lg:hidden bg-white border-b border-slate-200 px-3 py-2 overflow-x-auto">
                <div class="flex gap-3 min-w-max">
                    @if(auth()->user()->isTeacher() || auth()->user()->isIndividual())
                    <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-lg text-sm font-semibold {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('generate-soal') }}" class="px-3 py-1.5 rounded-lg text-sm font-semibold {{ request()->routeIs('generate-soal*') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                        Generate
                    </a>
                    <a href="{{ route('bank-soal') }}" class="px-3 py-1.5 rounded-lg text-sm font-semibold {{ request()->routeIs('bank-soal*') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                        Bank Soal
                    </a>
                    @else
                    <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.*') ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                        Admin Panel
                    </a>
                    @endif
                    <a href="{{ route('profile.edit') }}" class="px-3 py-1.5 rounded-lg text-sm font-semibold {{ request()->routeIs('profile.edit') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                        Profil
                    </a>
                </div>
            </div>

            {{-- Page Content --}}
            <main class="flex-1">
                {{ $slot }}
            </main>

            {{-- Footer --}}
            <footer class="border-t border-slate-200 bg-white mt-auto">
                <div class="max-w-7xl mx-auto px-5 py-4 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md bg-blue-600 flex items-center justify-center text-white font-bold text-[10px]">
                            AI
                        </div>
                        <span class="text-sm font-semibold text-slate-700">EduSoal AI</span>
                        <span class="text-xs text-slate-400">© {{ date('Y') }}</span>
                    </div>

                    <div class="flex items-center gap-5 text-xs text-slate-400">
                        <span>Dibuat dengan ❤️ untuk pendidikan Indonesia</span>
                        <a href="{{ route('profile.edit') }}" class="hover:text-slate-600 transition">Profil</a>
                        <a href="{{ route('dashboard') }}" class="hover:text-slate-600 transition">Bantuan</a>
                    </div>
                </div>
            </footer>

        </div>

    </div>

    {{-- Toast notifikasi global — otomatis muncul kalau ada session flash
         (success/info/error), dipakai di semua halaman termasuk setelah
         edit/hapus soal. Auto-hilang setelah beberapa detik, bisa ditutup
         manual juga. --}}
    @if(session('success') || session('info') || session('error'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 4000)"
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed bottom-6 right-6 z-50 max-w-sm w-full"
        >
            @php
                $toastType = session('success') ? 'success' : (session('error') ? 'error' : 'info');
                $toastMessage = session('success') ?? session('error') ?? session('info');
                $toastStyle = [
                    'success' => ['bg' => 'bg-green-600', 'icon' => 'M5 13l4 4L19 7'],
                    'error' => ['bg' => 'bg-red-600', 'icon' => 'M6 18L18 6M6 6l12 12'],
                    'info' => ['bg' => 'bg-blue-600', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ][$toastType];
            @endphp
            <div class="{{ $toastStyle['bg'] }} text-white rounded-xl shadow-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $toastStyle['icon'] }}"/>
                </svg>
                <p class="text-sm font-medium flex-1">{{ $toastMessage }}</p>
                <button @click="show = false" class="flex-shrink-0 text-white/70 hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

</body>
</html>