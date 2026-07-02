<?php

namespace App\Services\Document;

use App\Models\QuestionSet;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Mengisi template Word custom guru/sekolah dengan data soal.
 *
 * Placeholder yang didukung di template:
 *   ${judul_soal}        - Judul bank soal
 *   ${mata_pelajaran}     - Mata pelajaran
 *   ${kelas}              - Kelas
 *   ${topik}              - Topik
 *   ${tanggal}            - Tanggal dibuat
 *   ${nama_sekolah}       - Nama sekolah guru
 *   ${kepala_sekolah}     - Nama kepala sekolah
 *   ${jumlah_soal}        - Total soal
 *
 * Untuk daftar soal, gunakan blok berulang dengan ${soal} sebagai
 * penanda baris yang akan diduplikasi otomatis oleh PhpWord.
 */
class TemplateExportService
{
    /**
     * Generate dokumen Word dari template dengan data soal terisi.
     *
     * @param  string  $templatePath   Path template di disk 'local'
     * @param  QuestionSet $questionSet
     * @param  bool  $includeAnswers   true = versi guru, false = versi siswa
     * @return string  Path file hasil generate (temporary)
     */
    public function generate(string $templatePath, QuestionSet $questionSet, bool $includeAnswers = true): string
    {
        $fullPath = Storage::disk('local')->path($templatePath);

        $processor = new TemplateProcessor($fullPath);

        // ── Isi placeholder umum ────────────────────────────────────────────
        $this->safeSetValue($processor, 'judul_soal', $questionSet->title);
        $this->safeSetValue($processor, 'mata_pelajaran', $questionSet->subject);
        $this->safeSetValue($processor, 'kelas', $questionSet->grade);
        $this->safeSetValue($processor, 'topik', $questionSet->topic);
        $this->safeSetValue($processor, 'tanggal', $questionSet->created_at->format('d F Y'));
        $this->safeSetValue($processor, 'jumlah_soal', (string) $questionSet->questions->count());
        $this->safeSetValue($processor, 'kesulitan', ucfirst($questionSet->difficulty));
        $this->safeSetValue($processor, 'kurikulum', $questionSet->curriculum === 'k13' ? 'Kurikulum 2013' : 'Kurikulum Merdeka');

        // ── Isi data sekolah jika guru terikat sekolah ──────────────────────
        $school = $questionSet->user?->school;
        $this->safeSetValue($processor, 'nama_sekolah', $school?->name ?? '');
        $this->safeSetValue($processor, 'kepala_sekolah', $school?->headmaster_name ?? '');
        $this->safeSetValue($processor, 'nip_kepala_sekolah', $school?->headmaster_nip ?? '');
        $this->safeSetValue($processor, 'alamat_sekolah', $school?->letterhead_address ?? $school?->address ?? '');

        // ── Isi daftar soal menggunakan cloneRow jika ada block ${soal} ─────
        $questions = $questionSet->questions;

        $variables = $processor->getVariables();
        $hasSoalPlaceholder = in_array('soal', $variables, true);

        if ($hasSoalPlaceholder && $questions->count() > 0) {
            $processor->cloneRow('soal', $questions->count());

            foreach ($questions as $i => $question) {
                $num = $i + 1;
                $processor->setValue("nomor#{$num}", (string) $num);
                $processor->setValue("soal#{$num}", $question->question_text);

                if ($questionSet->question_type === 'multiple_choice') {
                    $processor->setValue("opsi_a#{$num}", $question->option_a ?? '');
                    $processor->setValue("opsi_b#{$num}", $question->option_b ?? '');
                    $processor->setValue("opsi_c#{$num}", $question->option_c ?? '');
                    $processor->setValue("opsi_d#{$num}", $question->option_d ?? '');
                }

                if ($includeAnswers) {
                    $processor->setValue("jawaban#{$num}", $question->correct_answer ?? '');
                    $processor->setValue("pembahasan#{$num}", $question->explanation ?? '');
                } else {
                    $processor->setValue("jawaban#{$num}", '');
                    $processor->setValue("pembahasan#{$num}", '');
                }
            }
        }

        // Simpan ke file temporary
        $outputPath = tempnam(sys_get_temp_dir(), 'edusoal_template_') . '.docx';
        $processor->saveAs($outputPath);

        return $outputPath;
    }

    /**
     * Validasi apakah file template valid dan bisa diparse.
     */
    public function validateTemplate(string $fullPath): bool
    {
        try {
            new TemplateProcessor($fullPath);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * setValue yang aman — tidak throw exception jika placeholder tidak ada di template.
     */
    private function safeSetValue(TemplateProcessor $processor, string $key, string $value): void
    {
        try {
            $processor->setValue($key, htmlspecialchars($value));
        } catch (\Exception $e) {
            // Placeholder tidak ada di template — abaikan
        }
    }
}