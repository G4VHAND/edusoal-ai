<?php

namespace App\Services\Document;

use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\TextRun;

/**
 * Format teks hasil generate AI (soal, jawaban, pembahasan) secara konsisten
 * di semua tempat: tampilan web, export PDF, dan export Word.
 *
 * AI kadang mengembalikan teks dengan penanda markdown ringan:
 *   - **kata**   → ditebalkan (bold)
 *   - \n         → baris baru
 *
 * Sebelumnya penanda ini dihapus mentah-mentah di beberapa field (jadi
 * hilang begitu saja) dan tidak diproses sama sekali di field lain (jadi
 * muncul literal "**kata**" atau baris menyatu tanpa jeda saat export).
 * Class ini menyatukan cara memprosesnya jadi satu tempat.
 */
class TextFormatter
{
    /**
     * Pecah teks jadi baris → jadi segmen {text, bold}, berdasarkan
     * penanda **bold** dan baris baru.
     *
     * @return array<int, array<int, array{text: string, bold: bool}>> per baris, per segmen
     */
    protected static function parse(?string $text): array
    {
        if ($text === null || $text === '') {
            return [];
        }

        // Normalisasi jenis newline & spasi berlebih di akhir baris
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);

        $result = [];

        foreach ($lines as $line) {
            $line = rtrim($line);

            if ($line === '') {
                $result[] = [['text' => '', 'bold' => false]];

                continue;
            }

            $segments = [];
            // Pecah berdasarkan **...** — tangkap juga teksnya
            $parts = preg_split('/\*\*(.+?)\*\*/', $line, -1, PREG_SPLIT_DELIM_CAPTURE);

            foreach ($parts as $i => $part) {
                if ($part === '') {
                    continue;
                }

                // Index ganjil hasil PREG_SPLIT_DELIM_CAPTURE = isi di dalam **...**
                $segments[] = ['text' => $part, 'bold' => $i % 2 === 1];
            }

            $result[] = $segments ?: [['text' => $line, 'bold' => false]];
        }

        return $result;
    }

    /**
     * Ubah teks jadi HTML aman (escaped) dengan <strong> untuk bold dan
     * <br> untuk baris baru. Dipakai di Blade (tampilan web & export PDF).
     */
    public static function toHtml(?string $text): string
    {
        $lines = self::parse($text);

        if (empty($lines)) {
            return '';
        }

        $htmlLines = [];

        foreach ($lines as $segments) {
            $html = '';

            foreach ($segments as $segment) {
                $escaped = e($segment['text']);
                $html .= $segment['bold'] ? "<strong>{$escaped}</strong>" : $escaped;
            }

            $htmlLines[] = $html;
        }

        return implode('<br>', $htmlLines);
    }

    /**
     * Tambahkan teks (dengan bold + baris baru) ke container PhpWord yang
     * sudah ada, misalnya Section biasa pada export Word format standar.
     *
     * Tiap baris dibuat lewat addTextRun() (satu paragraph per baris) supaya
     * beberapa segmen dalam satu baris (mis. "Jawaban: " + "A" yang bold)
     * tetap nyambung di baris yang sama, bukan malah pecah jadi baris
     * terpisah seperti kalau dipanggil addText() langsung per segmen.
     *
     * @param  array  $paragraphStyle  mis. ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH] untuk rata kiri-kanan (justify)
     * @param  array|null  $prefix  mis. ['text' => '1. ', 'style' => ['bold' => true]] — label
     *                              (nomor soal, "Jawaban:", dst.) yang SELALU tebal, ditaruh
     *                              di baris pertama yang sama, terpisah dari $baseStyle supaya
     *                              body teks tidak ikut ketebalan kalau memang tidak diminta.
     *                              Ini penting supaya **bold** dari AI di dalam body tetap
     *                              terlihat menonjol — kalau seluruh baris dipaksa bold, kata
     *                              yang ditandai AI jadi tidak beda dari teks sekitarnya.
     */
    public static function applyToContainer(AbstractContainer $container, ?string $text, array $baseStyle = [], array $paragraphStyle = [], ?array $prefix = null): void
    {
        $lines = self::parse($text);

        if (empty($lines) && $prefix !== null) {
            $lines = [[]];
        }

        foreach ($lines as $index => $segments) {
            $run = $container->addTextRun($paragraphStyle ?: null);

            if ($index === 0 && $prefix !== null && $prefix['text'] !== '') {
                $run->addText($prefix['text'], $prefix['style'] ?? ['bold' => true]);
            }

            foreach ($segments as $segment) {
                if ($segment['text'] === '') {
                    continue;
                }

                $style = $segment['bold'] ? array_merge($baseStyle, ['bold' => true]) : $baseStyle;
                $run->addText($segment['text'], $style);
            }
        }
    }

    /**
     * Bangun TextRun berdiri sendiri (dengan bold + baris baru) untuk
     * dipakai bersama TemplateProcessor::setComplexValue() saat mengisi
     * placeholder di template Word custom milik sekolah.
     *
     * @param  array  $paragraphStyle  mis. ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH] untuk rata kiri-kanan (justify)
     */
    public static function toTextRun(?string $text, array $baseStyle = [], array $paragraphStyle = []): TextRun
    {
        $textRun = new TextRun($paragraphStyle ?: null);

        $lines = self::parse($text);

        foreach ($lines as $i => $segments) {
            if ($i > 0) {
                $textRun->addTextBreak(1);
            }

            foreach ($segments as $segment) {
                if ($segment['text'] === '') {
                    continue;
                }

                $style = $segment['bold'] ? array_merge($baseStyle, ['bold' => true]) : $baseStyle;
                $textRun->addText($segment['text'], $style);
            }
        }

        return $textRun;
    }
}
