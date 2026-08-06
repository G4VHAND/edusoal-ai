@php
    $faqs = [
        [
            'q' => 'Bagaimana cara generate soal baru?',
            'a' => 'Buka menu "Generate Soal" di sidebar, isi mata pelajaran, kelas, topik, jumlah soal, dan jenis soal (pilihan ganda/essay). Setelah dikirim, sistem akan memproses di latar belakang — kamu bisa pantau progresnya lewat halaman "Riwayat Generate".',
        ],
        [
            'q' => 'Kenapa quota AI saya habis / generate ditolak?',
            'a' => 'Setiap paket punya batas jumlah generate per bulan. Cek sisa quota di kartu "Quota AI" pada dashboard atau sidebar. Quota akan reset otomatis di awal periode langganan berikutnya. Untuk sekolah, hubungi admin sekolah untuk cek atau upgrade paket.',
        ],
        [
            'q' => 'Generate saya gagal, apa yang harus dilakukan?',
            'a' => 'Buka "Riwayat Generate", cari entri yang berstatus "Gagal" — di situ ada pesan error singkat penyebabnya (biasanya masalah sementara dari provider AI). Coba generate ulang dengan topik/jumlah soal yang sama; kalau masih gagal berkali-kali, hubungi tim support.',
        ],
        [
            'q' => 'Bagaimana cara export soal ke PDF atau Word?',
            'a' => 'Buka detail bank soal yang sudah selesai digenerate, lalu pilih format export yang tersedia: PDF versi guru (dengan kunci jawaban), PDF versi siswa, Word polos, atau Word dengan template kop surat sekolah (jika sudah diatur admin sekolah).',
        ],
        [
            'q' => 'Apa bedanya AI Provider (Gemini vs Groq)?',
            'a' => 'Keduanya sama-sama bisa generate soal; bedanya di kecepatan dan gaya jawaban. Untuk guru dan individual, provider mengikuti pengaturan sekolah (atau default sistem). Admin sekolah bisa mengganti provider aktif lewat menu "Provider AI".',
        ],
        [
            'q' => 'Bagaimana cara mengedit soal yang salah setelah digenerate AI?',
            'a' => 'Buka detail bank soal, klik soal yang ingin diedit, lalu ubah teks pertanyaan, opsi jawaban, atau pembahasan secara manual. Perubahan ini tidak akan mengubah kuota AI karena tidak memanggil AI lagi.',
        ],
        [
            'q' => 'Saya guru di sebuah sekolah, kenapa tidak bisa akses Template Dokumen?',
            'a' => 'Pengelolaan template dokumen sekolah memang khusus untuk admin sekolah dan user individual. Sebagai guru, saat export kamu otomatis memakai template default yang sudah diatur admin sekolahmu — tidak perlu mengatur sendiri.',
        ],
    ];
@endphp

<x-app-layout>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full max-w-[1000px] mx-auto px-5 lg:px-8 py-8">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-1">Bantuan</h1>
                <p class="text-sm text-slate-500">Pusat bantuan, FAQ, dan cara menghubungi tim EduSoal AI.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 space-y-3">
                    @foreach($faqs as $faq)
                    <details class="group bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5 open:pb-5">
                        <summary class="flex items-center justify-between gap-3 cursor-pointer list-none">
                            <span class="text-sm font-semibold text-slate-900">{{ $faq['q'] }}</span>
                            <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                            </svg>
                        </summary>
                        <p class="text-sm text-slate-500 mt-3 leading-relaxed">{{ $faq['a'] }}</p>
                    </details>
                    @endforeach
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                            </svg>
                        </div>
                        <h2 class="text-sm font-bold text-slate-900 mb-1">Masih butuh bantuan?</h2>
                        <p class="text-xs text-slate-500 mb-4">Tim kami siap bantu lewat email — biasanya respons dalam 1x24 jam kerja.</p>
                        <a href="mailto:support@edusoal.ai"
                           class="block text-center w-full bg-blue-600 text-white font-semibold text-sm py-2.5 rounded-xl hover:bg-blue-700 transition">
                            support@edusoal.ai
                        </a>
                    </div>

                    <div class="bg-white rounded-2xl shadow-[0_2px_10px_-2px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 p-5">
                        <h2 class="text-sm font-bold text-slate-900 mb-3">Tautan cepat</h2>
                        <div class="space-y-2 text-sm">
                            <a href="{{ route('generate-soal') }}" class="flex items-center justify-between text-slate-600 hover:text-blue-600">
                                Generate Soal <span>→</span>
                            </a>
                            <a href="{{ route('riwayat-generate') }}" class="flex items-center justify-between text-slate-600 hover:text-blue-600">
                                Riwayat Generate <span>→</span>
                            </a>
                            <a href="{{ route('profile.edit') }}" class="flex items-center justify-between text-slate-600 hover:text-blue-600">
                                Pengaturan Akun <span>→</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
