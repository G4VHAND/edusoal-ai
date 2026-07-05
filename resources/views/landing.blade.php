<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EduSoal AI — Generate Soal Ujian dengan AI</title>
    <meta name="description" content="Platform AI untuk guru membuat bank soal pilihan ganda dan essay berkualitas tinggi berbasis Taksonomi Bloom, sesuai Kurikulum Merdeka dan K13.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Figtree', sans-serif; }
    </style>
</head>
<body class="bg-white text-slate-800 antialiased">

    {{-- ============ NAVBAR ============ --}}
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-violet-600 flex items-center justify-center text-white font-bold">
                    AI
                </div>
                <span class="font-bold text-xl text-slate-900">EduSoal AI</span>
            </div>

            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#fitur" class="hover:text-blue-600 transition">Fitur</a>
                <a href="#cara-kerja" class="hover:text-blue-600 transition">Cara Kerja</a>
                <a href="#harga" class="hover:text-blue-600 transition">Harga</a>
                <a href="#faq" class="hover:text-blue-600 transition">FAQ</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 hover:text-blue-600 transition">
                    Masuk
                </a>
                <a href="{{ route('register') }}"
                   class="bg-blue-600 text-white text-sm font-semibold px-5 py-2.5 rounded-xl hover:bg-blue-700 transition shadow-sm">
                    Daftar Gratis
                </a>
            </div>
        </div>
    </header>

    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-32 -right-32 w-96 h-96 bg-white/10 rounded-full"></div>
            <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-white/10 rounded-full"></div>
        </div>

        <div class="relative max-w-5xl mx-auto px-6 py-24 text-center">
            <div class="inline-flex items-center gap-2 bg-white/15 text-white px-4 py-2 rounded-full text-sm font-semibold mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 3v18M3 12h18"/>
                </svg>
                Ditenagai Google Gemini AI
            </div>

            <h1 class="text-4xl md:text-6xl font-extrabold text-white leading-tight mb-6">
                Buat Soal Ujian<br>
                <span class="text-blue-200">dalam Hitungan Detik</span>
            </h1>

            <p class="text-blue-100 text-lg md:text-xl max-w-2xl mx-auto mb-10">
                Platform AI untuk guru membuat bank soal pilihan ganda dan essay berkualitas tinggi,
                sesuai Taksonomi Bloom, Kurikulum Merdeka maupun K13.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}"
                   class="w-full sm:w-auto bg-white text-blue-700 font-bold px-8 py-4 rounded-2xl shadow-lg hover:bg-slate-50 transition text-lg">
                    Coba Gratis Sekarang
                </a>
                <a href="#harga"
                   class="w-full sm:w-auto border-2 border-white/40 text-white font-semibold px-8 py-4 rounded-2xl hover:bg-white/10 transition text-lg">
                    Lihat Paket Sekolah
                </a>
            </div>

            <p class="text-blue-200 text-sm mt-6">
                Gratis 10 soal/bulan · Tanpa kartu kredit · Setup 2 menit
            </p>
        </div>
    </section>

    {{-- ============ STATS BAR ============ --}}
    <section class="bg-white border-b border-slate-100">
        <div class="max-w-5xl mx-auto px-6 py-10 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <div>
                <p class="text-3xl font-extrabold text-blue-600">C1–C6</p>
                <p class="text-sm text-slate-500 mt-1">Level Taksonomi Bloom</p>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-blue-600">SD–SMK</p>
                <p class="text-sm text-slate-500 mt-1">Semua Jenjang</p>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-blue-600">2</p>
                <p class="text-sm text-slate-500 mt-1">Kurikulum Didukung</p>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-blue-600">&lt;60s</p>
                <p class="text-sm text-slate-500 mt-1">Waktu Generate Soal</p>
            </div>
        </div>
    </section>

    {{-- ============ FITUR ============ --}}
    <section id="fitur" class="max-w-6xl mx-auto px-6 py-24">
        <div class="text-center mb-16">
            <p class="text-blue-600 font-semibold text-sm uppercase tracking-wider mb-3">Fitur Utama</p>
            <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900">
                Semua yang Guru Butuhkan
            </h2>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @foreach([
                ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => 'Sesuai Taksonomi Bloom', 'desc' => 'Soal otomatis dipetakan ke level C1–C6 sesuai tingkat kesulitan yang dipilih — tidak sekadar label "mudah/sulit".'],
                ['icon' => 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z', 'title' => 'Generate dari Materi Sendiri', 'desc' => 'Upload PDF/DOCX materi ajar, AI akan membuat soal berdasarkan isi materi tersebut — bukan asal generate.'],
                ['icon' => 'M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-6 0h6m-6 0H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-2', 'title' => 'Kurikulum Merdeka & K13', 'desc' => 'Pilih kurikulum sekolah Anda — instruksi AI otomatis menyesuaikan Capaian Pembelajaran atau Kompetensi Dasar.'],
                ['icon' => 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z', 'title' => 'Soal Berbasis Gambar', 'desc' => 'Upload diagram, peta, atau grafik — AI merekomendasikan gambar yang dibutuhkan tiap soal secara otomatis.'],
                ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'title' => 'Export Siap Pakai', 'desc' => 'Unduh langsung ke PDF atau Word, lengkap dengan kop surat sekolah — tanpa perlu copy-paste manual.'],
                ['icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4', 'title' => 'Multi-Sekolah & Guru', 'desc' => 'Admin sekolah kelola akun guru, pantau bank soal, dan atur quota — semua dalam satu dashboard.'],
            ] as $feat)
            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100 hover:border-blue-200 hover:bg-blue-50/30 transition">
                <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $feat['icon'] }}"/>
                    </svg>
                </div>
                <h3 class="font-bold text-slate-900 mb-2">{{ $feat['title'] }}</h3>
                <p class="text-slate-500 text-sm leading-relaxed">{{ $feat['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </section>

    {{-- ============ CARA KERJA ============ --}}
    <section id="cara-kerja" class="bg-slate-50 py-24">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-16">
                <p class="text-blue-600 font-semibold text-sm uppercase tracking-wider mb-3">Cara Kerja</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900">
                    3 Langkah, Soal Siap Pakai
                </h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                @foreach([
                    ['num' => '1', 'title' => 'Isi Detail Soal', 'desc' => 'Pilih mata pelajaran, kelas, topik, tingkat kesulitan, kurikulum, dan jenis asesmen.'],
                    ['num' => '2', 'title' => 'AI Generate Otomatis', 'desc' => 'Sistem memproses di background — Anda bisa lanjut kerja lain sambil menunggu.'],
                    ['num' => '3', 'title' => 'Review & Export', 'desc' => 'Periksa soal, lalu unduh ke PDF/Word — siap dicetak dan digunakan di kelas.'],
                ] as $step)
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-blue-600 to-violet-600 text-white text-2xl font-extrabold flex items-center justify-center mb-5 shadow-lg">
                        {{ $step['num'] }}
                    </div>
                    <h3 class="font-bold text-slate-900 mb-2">{{ $step['title'] }}</h3>
                    <p class="text-slate-500 text-sm">{{ $step['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ HARGA (dinamis dari database) ============ --}}
    <section id="harga" class="max-w-6xl mx-auto px-6 py-24">
        <div class="text-center mb-16">
            <p class="text-blue-600 font-semibold text-sm uppercase tracking-wider mb-3">Harga</p>
            <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4">
                Paket untuk Setiap Kebutuhan
            </h2>
            <p class="text-slate-500 max-w-xl mx-auto">
                Mulai gratis, upgrade kapan saja sesuai pertumbuhan sekolah Anda.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($plans as $plan)
            @php
                $isPopular = $plan->slug === 'pro';
                // PENTING: class harus lengkap & literal di file ini supaya
                // ke-scan Tailwind saat build. Jangan interpolasi jadi
                // "text-{{ $color }}-500" — itu TIDAK akan ter-generate.
                $checkColorClasses = [
                    'free'       => 'text-slate-500',
                    'basic'      => 'text-blue-500',
                    'pro'        => 'text-violet-500',
                    'enterprise' => 'text-amber-500',
                ];
                $checkColorClass = $checkColorClasses[$plan->slug] ?? 'text-slate-500';
            @endphp
            <div class="rounded-2xl border-2 p-6 flex flex-col
                {{ $isPopular ? 'border-violet-500 shadow-xl relative' : 'border-slate-200' }}">

                @if($isPopular)
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-violet-600 text-white text-xs font-bold px-4 py-1 rounded-full">
                    PALING POPULER
                </span>
                @endif

                <h3 class="font-bold text-lg text-slate-900">{{ $plan->name }}</h3>
                <p class="text-3xl font-extrabold text-slate-900 mt-3">
                    {{ $plan->formattedPriceMonthly() }}
                    @if($plan->price_monthly > 0)
                        <span class="text-sm font-medium text-slate-400">/bulan</span>
                    @endif
                </p>

                <ul class="mt-6 space-y-3 flex-1">
                    @foreach($plan->features ?? [] as $feature)
                    <li class="flex items-start gap-2 text-sm text-slate-600">
                        <svg class="w-5 h-5 {{ $checkColorClass }} flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>

                <a href="{{ route('register') }}"
                   class="mt-6 block text-center font-semibold py-3 rounded-xl transition
                   {{ $isPopular ? 'bg-violet-600 text-white hover:bg-violet-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    {{ $plan->price_monthly == 0 ? 'Mulai Gratis' : 'Pilih Paket' }}
                </a>
            </div>
            @endforeach
        </div>

        <p class="text-center text-slate-500 text-sm mt-10">
            Untuk sekolah dengan kebutuhan khusus,
            <a href="mailto:hello@edusoal.ai" class="text-blue-600 font-semibold hover:underline">hubungi tim kami</a>
            untuk penawaran khusus.
        </p>
    </section>

    {{-- ============ FAQ ============ --}}
    <section id="faq" class="bg-slate-50 py-24">
        <div class="max-w-3xl mx-auto px-6">
            <div class="text-center mb-16">
                <p class="text-blue-600 font-semibold text-sm uppercase tracking-wider mb-3">FAQ</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900">
                    Pertanyaan yang Sering Ditanyakan
                </h2>
            </div>

            <div class="space-y-4">
                @foreach([
                    ['q' => 'Apakah soal yang dihasilkan AI akurat?', 'a' => 'AI menggunakan sistem anti-halusinasi — setiap soal menyertakan kolom sumber yang menunjukkan dasar pembuatannya. Kami tetap menyarankan guru mereview soal sebelum digunakan dalam ujian resmi.'],
                    ['q' => 'Bagaimana jika sekolah kami masih pakai Kurikulum 2013?', 'a' => 'EduSoal AI mendukung Kurikulum Merdeka dan K13 — Anda tinggal pilih saat generate soal, instruksi AI otomatis menyesuaikan.'],
                    ['q' => 'Apakah bisa upload materi sendiri untuk dijadikan soal?', 'a' => 'Bisa. Upload file PDF, DOC, atau DOCX, dan AI akan membuat soal berdasarkan isi materi tersebut, bukan pengetahuan umum.'],
                    ['q' => 'Bagaimana cara sekolah kami mulai berlangganan?', 'a' => 'Guru bisa mulai gratis secara individual, atau admin sekolah bisa mendaftarkan seluruh sekolah dengan menghubungi tim kami untuk setup akun massal.'],
                ] as $faq)
                <details class="bg-white rounded-xl border border-slate-200 p-5 group">
                    <summary class="font-semibold text-slate-900 cursor-pointer flex items-center justify-between">
                        {{ $faq['q'] }}
                        <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <p class="text-slate-500 text-sm mt-3">{{ $faq['a'] }}</p>
                </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ CTA FINAL ============ --}}
    <section class="bg-gradient-to-br from-blue-600 to-violet-700 py-20">
        <div class="max-w-3xl mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-4">
                Siap Menghemat Waktu Membuat Soal?
            </h2>
            <p class="text-blue-100 text-lg mb-8">
                Bergabung dengan guru-guru yang sudah beralih ke EduSoal AI.
            </p>
            <a href="{{ route('register') }}"
               class="inline-block bg-white text-blue-700 font-bold px-8 py-4 rounded-2xl shadow-lg hover:bg-slate-50 transition text-lg">
                Daftar Gratis Sekarang
            </a>
        </div>
    </section>

    {{-- ============ FOOTER ============ --}}
    <footer class="bg-slate-900 text-slate-400 py-12">
        <div class="max-w-6xl mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-600 to-violet-600 flex items-center justify-center text-white font-bold text-sm">
                        AI
                    </div>
                    <span class="font-bold text-white">EduSoal AI</span>
                </div>

                <p class="text-sm">
                    &copy; {{ date('Y') }} EduSoal AI · Farkatech · Dibuat untuk pendidikan Indonesia
                </p>

                <div class="flex gap-4 text-sm">
                    <a href="{{ route('login') }}" class="hover:text-white transition">Masuk</a>
                    <a href="{{ route('register') }}" class="hover:text-white transition">Daftar</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
