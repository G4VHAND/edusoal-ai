<?php

namespace App\Services\Document;

use App\Models\QuestionSet;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Bangun dokumen Word format standar (tanpa template custom sekolah/guru).
 *
 * Dipakai langsung oleh tombol "Word Soal Siswa", dan juga sebagai fallback
 * otomatis di BankSoalExportService::withTemplate() saat sekolah/guru belum
 * punya template default — supaya guru tetap bisa export tanpa pernah
 * melihat pesan error soal "upload template dulu".
 */
class PlainWordExportService
{
    /**
     * @return string Path file .docx sementara (belum di-download, belum dihapus)
     */
    public function build(QuestionSet $questionSet, bool $includeAnswers): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();

        $section->addText(
            $questionSet->title,
            ['bold' => true, 'size' => 18]
        );

        $section->addText(
            $questionSet->subject.
            ' | Kelas '.$questionSet->grade.
            ' | '.$questionSet->topic
        );

        $section->addTextBreak(1);

        foreach ($questionSet->questions as $index => $question) {
            $justify = ['alignment' => Jc::BOTH];

            TextFormatter::applyToContainer(
                $section,
                $question->question_text,
                [],
                $justify,
                ['text' => ($index + 1).'. ', 'style' => ['bold' => true]]
            );

            // Sisipkan gambar jika ada
            if ($question->hasImage()) {
                $imagePath = Storage::disk('local')->path($question->image_path);

                if (file_exists($imagePath)) {
                    try {
                        $section->addImage($imagePath, [
                            'width' => 400,
                            'height' => 250,
                            'alignment' => Jc::CENTER,
                            'wrappingStyle' => 'inline',
                            'marginTop' => 5,
                            'marginBottom' => 5,
                        ]);
                    } catch (\Exception $e) {
                        // Jika gambar gagal disisipkan, tambahkan keterangan
                        $section->addText('[Gambar tidak dapat ditampilkan]', ['italic' => true, 'color' => '999999']);
                    }
                }
            } elseif ($question->needs_image) {
                // Soal butuh gambar tapi belum diupload — beri placeholder
                $section->addText(
                    '[GAMBAR: '.($question->image_recommendation ?? 'Sisipkan gambar di sini').']',
                    ['italic' => true, 'color' => 'CC6600']
                );
            }

            if ($questionSet->question_type === 'multiple_choice') {
                TextFormatter::applyToContainer($section, 'A. '.$question->option_a, [], $justify);
                TextFormatter::applyToContainer($section, 'B. '.$question->option_b, [], $justify);
                TextFormatter::applyToContainer($section, 'C. '.$question->option_c, [], $justify);
                TextFormatter::applyToContainer($section, 'D. '.$question->option_d, [], $justify);
            }

            if ($includeAnswers) {
                TextFormatter::applyToContainer(
                    $section,
                    $question->correct_answer,
                    ['color' => '2563EB'],
                    $justify,
                    ['text' => 'Jawaban: ', 'style' => ['bold' => true, 'color' => '2563EB']]
                );

                if (! empty($question->explanation)) {
                    TextFormatter::applyToContainer(
                        $section,
                        $question->explanation,
                        ['italic' => true, 'color' => '475569'],
                        $justify,
                        ['text' => 'Pembahasan: ', 'style' => ['bold' => true, 'italic' => true, 'color' => '475569']]
                    );
                }
            }

            $section->addTextBreak(1);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'edusoal_word_');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);

        return $tempFile;
    }
}
