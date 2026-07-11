<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\QuestionSet;
use App\Services\Document\TemplateExportService;
use App\Services\Document\TextFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class BankSoalController extends Controller
{
    public function index()
    {
        $search = request('search');
        $questionType = request('question_type');
        $difficulty = request('difficulty');

        $questionSets = QuestionSet::where('user_id', auth()->id())
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('grade', 'like', "%{$search}%")
                        ->orWhere('topic', 'like', "%{$search}%")
                        ->orWhere('difficulty', 'like', "%{$search}%");
                });
            })
            ->when($questionType, fn ($q) => $q->where('question_type', $questionType))
            ->when($difficulty, fn ($q) => $q->where('difficulty', $difficulty))
            ->latest()
            ->paginate(20)
            ->withQueryString(); // Pertahankan filter saat ganti halaman

        return view('bank-soal.index', compact(
            'questionSets',
            'search',
            'questionType',
            'difficulty'
        ));
    }

    public function exportPdf(QuestionSet $questionSet)
    {
        $this->authorize('export', $questionSet);

        $questionSet->load('questions');

        $pdf = Pdf::loadView('bank-soal.export-pdf', compact('questionSet'))
            ->setPaper('A4', 'portrait');

        return $pdf->download($questionSet->title.'.pdf');
    }

    public function exportStudentPdf(QuestionSet $questionSet)
    {
        $this->authorize('export', $questionSet);

        $questionSet->load('questions');

        $pdf = Pdf::loadView('bank-soal.export-student-pdf', compact('questionSet'))
            ->setPaper('A4', 'portrait');

        return $pdf->download($questionSet->title.' - Soal Siswa.pdf');
    }

    public function exportStudentWord(QuestionSet $questionSet)
    {
        $this->authorize('export', $questionSet);

        $questionSet->load('questions');

        $tempFile = $this->buildPlainWord($questionSet, includeAnswers: false);
        $fileName = $questionSet->title.'-Soal.docx';

        return response()
            ->download($tempFile, $fileName)
            ->deleteFileAfterSend(true);
    }

    /**
     * Bangun dokumen Word format standar (tanpa template custom).
     *
     * Dipakai langsung oleh tombol "Word Soal Siswa", dan juga sebagai
     * fallback otomatis di exportWithTemplate() saat sekolah/guru belum
     * punya template default — supaya guru tetap bisa export tanpa pernah
     * melihat pesan error soal "upload template dulu".
     */
    private function buildPlainWord(QuestionSet $questionSet, bool $includeAnswers): string
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

    /**
     * Export menggunakan template Word custom milik guru/sekolah.
     * Jika tidak ada template default, fallback ke export standar.
     */
    public function exportWithTemplate(Request $request, QuestionSet $questionSet, TemplateExportService $service)
    {
        $this->authorize('export', $questionSet);

        $questionSet->load('questions', 'user.school');

        $type = $request->get('type', 'guru'); // guru | siswa
        $templateId = $request->get('template_id');

        $user = $questionSet->user;
        $schoolId = $user?->school_id;
        $currentUser = auth()->user();

        // Cari template dengan prioritas:
        // 1. Template ID eksplisit jika ada
        // 2. Template default milik sekolah (school_id match)
        // 3. Template default milik user yang sedang login
        $template = null;

        if ($templateId) {
            $template = DocumentTemplate::find($templateId);

            if ($template) {
                // Validasi kepemilikan: sama seperti pola di destroy()/setDefault()
                // (DocumentTemplateController) — admin sekolah hanya boleh pakai
                // template sekolahnya sendiri, guru hanya boleh pakai template
                // miliknya sendiri (atau template default sekolah gurunya).
                $owns = $currentUser->isSchoolAdmin()
                    ? $template->school_id === $currentUser->school_id
                    : (
                        $template->user_id === $currentUser->id
                        || ($schoolId && $template->school_id === $schoolId)
                    );

                abort_unless($owns, 403, 'Anda tidak memiliki akses ke template ini.');
            }
        } else {
            // Cari dari sekolah guru yang buat soal
            if ($schoolId) {
                $template = DocumentTemplate::where('school_id', $schoolId)
                    ->where('type', $type)
                    ->where('is_default', true)
                    ->first();
            }

            // Fallback: cari dari sekolah user yang sedang login
            if (! $template && $currentUser->school_id) {
                $template = DocumentTemplate::where('school_id', $currentUser->school_id)
                    ->where('type', $type)
                    ->where('is_default', true)
                    ->first();
            }

            // Fallback: template personal user yang login
            if (! $template) {
                $template = DocumentTemplate::where('user_id', $currentUser->id)
                    ->where('type', $type)
                    ->where('is_default', true)
                    ->first();
            }
        }

        // Tidak ada template (atau file-nya hilang di server) — jangan
        // tampilkan error yang menyuruh user "upload template dulu", karena
        // guru tidak perlu tahu konsep template sama sekali. Cukup fallback
        // diam-diam ke format standar, seolah memang begitu dari awal.
        if (! $template || ! Storage::disk('local')->exists($template->file_path)) {
            $tempFile = $this->buildPlainWord($questionSet, includeAnswers: $type === 'guru');
            $fileName = $questionSet->title.' - '.ucfirst($type).'.docx';

            return response()
                ->download($tempFile, $fileName)
                ->deleteFileAfterSend(true);
        }

        try {
            $outputPath = $service->generate(
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
            $tempFile = $this->buildPlainWord($questionSet, includeAnswers: $type === 'guru');
            $fileName = $questionSet->title.' - '.ucfirst($type).'.docx';

            return response()
                ->download($tempFile, $fileName)
                ->deleteFileAfterSend(true);
        }

        $fileName = $questionSet->title.' - '.ucfirst($type).'.docx';

        return response()
            ->download($outputPath, $fileName)
            ->deleteFileAfterSend(true);
    }
}
