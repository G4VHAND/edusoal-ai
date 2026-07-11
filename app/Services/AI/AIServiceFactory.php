<?php

namespace App\Services\AI;

class AIServiceFactory
{
    /**
     * Membuat instance AI service berdasarkan nama provider.
     *
     * Menggantikan switch/case di QuestionSetController agar
     * controller tidak perlu tahu class mana yang dipakai.
     *
     * @throws \InvalidArgumentException jika provider tidak didukung
     */
    public static function make(string $provider): AIService
    {
        return match ($provider) {
            'gemini' => new GeminiService,
            'groq' => new GroqService,
            default => throw new \InvalidArgumentException(
                "Provider AI tidak didukung: {$provider}"
            ),
        };
    }

    public static function supported(): array
    {
        return config('ai.supported_providers', ['gemini', 'groq']);
    }

    /**
     * Label & keterangan tampilan untuk tiap provider yang didukung —
     * SATU-SATUNYA tempat sumber label provider dipakai bersama oleh
     * halaman admin (pilih provider sekolah) dan form generate soal
     * individual (pilih provider sendiri).
     *
     * Saat ini cuma 'gemini' & 'groq' yang aktif (lihat supported()) —
     * kalau nanti mau menambah provider baru, cukup:
     *   1. Buat *Service baru yang implement AIService
     *   2. Tambahkan case-nya di make() di atas
     *   3. Tambahkan key-nya di config('ai.supported_providers')
     *   4. Tambahkan entry label di sini
     * — tidak perlu ubah apapun di Blade/controller lain, karena semua
     * tempat pemilihan provider sudah menarik data dari sini.
     */
    public static function labeled(): array
    {
        $labels = [
            'gemini' => [
                'label' => 'Google Gemini',
                'description' => 'Mendukung soal berbasis gambar (Vision AI). Direkomendasikan untuk kebanyakan sekolah.',
            ],
            'groq' => [
                'label' => 'Groq (Llama)',
                'description' => 'Respons lebih cepat, tapi belum mendukung soal berbasis gambar.',
            ],
        ];

        return collect(self::supported())
            ->mapWithKeys(fn ($key) => [
                $key => $labels[$key] ?? ['label' => ucfirst($key), 'description' => ''],
            ])
            ->all();
    }
}
