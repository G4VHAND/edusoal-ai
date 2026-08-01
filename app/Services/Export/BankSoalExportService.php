<?php

namespace App\Services\Export;

use App\Models\QuestionSet;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Document\DocumentTemplateResolver;
use App\Services\Document\PlainWordExportService;
use App\Services\Document\TemplateExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Orkestrator semua jenis export Bank Soal: PDF guru/siswa, Word polos,
 * dan Word pakai template custom sekolah/guru.
 *
 * BankSoalController hanya boleh: authorize() lalu panggil method di sini.
 * Semua keputusan format apa yang dipakai, template mana yang dicari, dan
 * apa yang di-log ada di service ini.
 */
class BankSoalExportService
{
    public function __construct(
        private readonly TemplateExportService $templateExportService,
        private readonly PlainWordExportService $plainWordExportService,
        private readonly DocumentTemplateResolver $templateResolver,
    ) {}

    public function guruPdf(QuestionSet $questionSet)
    {
        $questionSet->load('questions');

        $pdf = Pdf::loadView('bank-soal.export-pdf', compact('questionSet'))
            ->setPaper('A4', 'portrait');

        AuditLogService::log(
            module: 'Export',
            event: 'pdf',
            description: "Download PDF Guru '{$questionSet->title}'",
            properties: ['question_set_id' => $questionSet->id]
        );

        return $pdf->download($this->sanitizeFilename($questionSet->title).'.pdf');
    }

    public function siswaPdf(QuestionSet $questionSet)
    {
        $questionSet->load('questions');

        $pdf = Pdf::loadView('bank-soal.export-student-pdf', compact('questionSet'))
            ->setPaper('A4', 'portrait');

        AuditLogService::log(
            module: 'Export',
            event: 'pdf',
            description: "Download PDF Siswa '{$questionSet->title}'",
            properties: ['question_set_id' => $questionSet->id]
        );

        return $pdf->download($this->sanitizeFilename($questionSet->title).' - Soal Siswa.pdf');
    }

    public function siswaWord(QuestionSet $questionSet)
    {
        $questionSet->load('questions');

        $tempFile = $this->plainWordExportService->build($questionSet, includeAnswers: false);
        $fileName = $this->sanitizeFilename($questionSet->title).'-Soal.docx';

        AuditLogService::log(
            module: 'Export',
            event: 'word',
            description: "Download Word Siswa '{$questionSet->title}'",
            properties: ['question_set_id' => $questionSet->id]
        );

        return response()
            ->download($tempFile, $fileName)
            ->deleteFileAfterSend(true);
    }

    /**
     * Export Word pakai template custom milik guru/sekolah. Kalau tidak
     * ada template default (atau file-nya rusak/gagal diparse), diam-diam
     * fallback ke format standar — guru tidak perlu tahu konsep template.
     */
    public function withTemplate(Request $request, QuestionSet $questionSet, User $currentUser)
    {
        $questionSet->load('questions', 'user.school');

        $type = $request->get('type', 'guru'); // guru | siswa
        $templateId = $request->get('template_id');
        $templateId = $templateId ? (int) $templateId : null;

        $template = $this->templateResolver->resolve($questionSet, $currentUser, $type, $templateId);

        if (! $template || ! Storage::disk('local')->exists($template->file_path)) {
            return $this->fallbackWord($questionSet, $type, templateId: null);
        }

        try {
            $outputPath = $this->templateExportService->generate(
                $template->file_path,
                $questionSet,
                includeAnswers: $type === 'guru'
            );
        } catch (\Exception $e) {
            \Log::error('Export template gagal, fallback ke format standar: '.$e->getMessage(), [
                'question_set_id' => $questionSet->id,
                'template_id' => $template->id,
                'trace' => $e->getTraceAsString(),
            ]);

            // Template rusak/gagal diparse — tetap beri user dokumennya
            // lewat fallback standar daripada dead-end error.
            return $this->fallbackWord($questionSet, $type, templateId: $template->id, error: $e->getMessage());
        }

        $fileName = $this->sanitizeFilename($questionSet->title).' - '.ucfirst($type).'.docx';

        AuditLogService::log(
            module: 'Export',
            event: 'word',
            description: 'Download Word '.ucfirst($type)." '{$questionSet->title}'",
            properties: ['question_set_id' => $questionSet->id, 'template_id' => $template->id]
        );

        return response()
            ->download($outputPath, $fileName)
            ->deleteFileAfterSend(true);
    }

    /**
     * Fallback ke Word format standar — dipakai baik saat memang tidak ada
     * template ($error null) maupun saat template ada tapi gagal diproses
     * ($error terisi). Deskripsi audit log dibedakan supaya riwayatnya
     * tetap bisa menjawab "kenapa" fallback terjadi.
     */
    private function fallbackWord(QuestionSet $questionSet, string $type, ?int $templateId, ?string $error = null)
    {
        $tempFile = $this->plainWordExportService->build($questionSet, includeAnswers: $type === 'guru');
        $fileName = $this->sanitizeFilename($questionSet->title).' - '.ucfirst($type).'.docx';

        $suffix = $error
            ? ' (template gagal, fallback standar)'
            : ' (format standar)';

        $properties = ['question_set_id' => $questionSet->id, 'template_id' => $templateId];

        if ($error) {
            $properties['error'] = $error;
        }

        AuditLogService::log(
            module: 'Export',
            event: 'word',
            description: 'Download Word '.ucfirst($type)." '{$questionSet->title}'".$suffix,
            properties: $properties
        );

        return response()
            ->download($tempFile, $fileName)
            ->deleteFileAfterSend(true);
    }

    /**
     * Bersihkan judul bank soal supaya aman dipakai sebagai nama file
     * download — judul adalah input bebas dari user (tidak divalidasi
     * karakter khusus saat generate soal), jadi karakter yang bermasalah
     * untuk nama file/HTTP header (mis. / \ : * ? " < > |) perlu dibuang.
     */
    private function sanitizeFilename(string $title): string
    {
        $clean = preg_replace('/[\/\\\\:*?"<>|\x00-\x1F]/', '', $title);
        $clean = trim($clean);

        return $clean !== '' ? $clean : 'Bank Soal';
    }
}
