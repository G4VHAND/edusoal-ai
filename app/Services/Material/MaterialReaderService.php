<?php

namespace App\Services\Material;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class MaterialReaderService
{
    /**
     * Ekstrak teks dari file materi yang sudah disimpan di disk 'local'.
     *
     * @param  string|null $path  Path relatif di dalam disk 'local'
     * @return string|null
     */
    public function extractText(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Gunakan disk 'local' (private), bukan 'public'
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $fullPath  = Storage::disk('local')->path($path);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf'  => $this->extractPdf($fullPath),
            'txt'  => $this->extractTxt($fullPath),
            'docx' => $this->extractDocx($fullPath),
            default => null,
        };
    }

    private function extractPdf(string $fullPath): ?string
    {
        try {
            $parser = new Parser();
            $pdf    = $parser->parseFile($fullPath);

            return trim($pdf->getText()) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function extractTxt(string $fullPath): ?string
    {
        $content = @file_get_contents($fullPath);

        return $content !== false ? trim($content) : null;
    }

    private function extractDocx(string $fullPath): ?string
    {
        try {
            $phpWord = IOFactory::load($fullPath);
            $text    = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }

            return trim($text) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
