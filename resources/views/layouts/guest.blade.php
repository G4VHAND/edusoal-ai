<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'EduSoal AI') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50">

<div class="min-h-screen flex">

    {{-- Kiri — Branding Panel --}}
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 via-blue-700 to-violet-700 flex-col justify-between p-12 relative overflow-hidden">

        {{-- Background decoration --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/5 rounded-full"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/5 rounded-full"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-white/3 rounded-full"></div>
        </div>

        {{-- Logo & Brand --}}
        <div class="relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <span class="text-white font-bold text-lg">AI</span>
                </div>
                <span class="text-white font-bold text-xl">EduSoal AI</span>
            </div>
        </div>

        {{-- Center Content --}}
        <div class="relative z-10">
            <h1 class="text-4xl font-bold text-white leading-tight mb-4">
                Buat Soal Ujian<br>Lebih Cepat<br>dengan AI
            </h1>
            <p class="text-blue-100 text-lg leading-relaxed mb-8">
                Platform cerdas untuk guru membuat bank soal pilihan ganda dan essay berkualitas tinggi berbasis Taksonomi Bloom.
            </p>

            {{-- Feature list --}}
            <div class="space-y-3">
                @foreach([
                    ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'text' => 'Generate soal dari materi PDF/DOCX'],
                    ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'text' => 'Standar Taksonomi Bloom Kurikulum Merdeka'],
                    ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'text' => 'Export ke PDF & Word langsung'],
                    ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'text' => 'Soal bergambar dengan rekomendasi visual AI'],
                ] as $feat)
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-200 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $feat['icon'] }}"/>
                    </svg>
                    <span class="text-blue-100 text-sm">{{ $feat['text'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Bottom --}}
        <div class="relative z-10">
            <p class="text-blue-200 text-sm">© {{ date('Y') }} EduSoal AI · Farkatech</p>
        </div>
    </div>

    {{-- Kanan — Form Panel --}}
    <div class="flex-1 flex flex-col justify-center items-center px-6 py-12 lg:px-16">

        {{-- Mobile logo --}}
        <div class="lg:hidden mb-8 text-center">
            <div class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-xl">
                <span class="font-bold">AI</span>
                <span class="font-semibold">EduSoal AI</span>
            </div>
        </div>

        <div class="w-full max-w-md">
            {{ $slot }}
        </div>

        <p class="mt-8 text-center text-xs text-slate-400">
            © {{ date('Y') }} EduSoal AI · Dibuat dengan ❤️ untuk pendidikan Indonesia
        </p>
    </div>

</div>

</body>
</html>