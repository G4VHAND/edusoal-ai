<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\QuestionSet;
use App\Services\Document\TemplateExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class BankSoalController extends Controller
{
    public function index()
    {
        $search       = request('search');
        $questionType = request('question_type');
        $difficulty   = request('difficulty');

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
            ->when($difficulty,   fn ($q) => $q->where('difficulty', $difficulty))
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

        return $pdf->download($questionSet->title . '.pdf');
    }

    public function exportStudentPdf(QuestionSet $questionSet)
    {
        $this->authorize('export', $questionSet);

        $questionSet->load('questions');

        $pdf = Pdf::loadView('bank-soal.export-student-pdf', compact('questionSet'))
            ->setPaper('A4', 'portrait');

        return $pdf->download($questionSet->title . ' - Soal Siswa.pdf');
    }

    public function exportStudentWord(QuestionSet $questionSet)
    {
        $this->authorize('export', $questionSet);

        $questionSet->load('questions');

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText(
            $questionSet->title,
            ['bold' => true, 'size' => 18]
        );

        $section->addText(
            $questionSet->subject .
            ' | Kelas ' . $questionSet->grade .
            ' | ' . $questionSet->topic
        );

        $section->addTextBreak(1);

        foreach ($questionSet->questions as $index => $question) {
            $section->addText(
                ($index + 1) . '. ' . $question->question_text,
                ['bold' => true]
            );

            // Sisipkan gambar jika ada
            if ($question->hasImage()) {
                $imagePath = Storage::disk('local')->path($question->image_path);

                if (file_exists($imagePath)) {
                    try {
                        $section->addImage($imagePath, [
                            'width'            => 400,
                            'height'           => 250,
                            'alignment'        => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                            'wrappingStyle'    => 'inline',
                            'marginTop'        => 5,
                            'marginBottom'     => 5,
                        ]);
                    } catch (\Exception $e) {
                        // Jika gambar gagal disisipkan, tambahkan keterangan
                        $section->addText('[Gambar tidak dapat ditampilkan]', ['italic' => true, 'color' => '999999']);
                    }
                }
            } elseif ($question->needs_image) {
                // Soal butuh gambar tapi belum diupload — beri placeholder
                $section->addText(
                    '[GAMBAR: ' . ($question->image_recommendation ?? 'Sisipkan gambar di sini') . ']',
                    ['italic' => true, 'color' => 'CC6600']
                );
            }

            if ($questionSet->question_type === 'multiple_choice') {
                $section->addText('A. ' . $question->option_a);
                $section->addText('B. ' . $question->option_b);
                $section->addText('C. ' . $question->option_c);
                $section->addText('D. ' . $question->option_d);
            }

            $section->addTextBreak(1);
        }

        $fileName = $questionSet->title . '-Soal.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'edusoal_word_');

        IOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);

        return response()
            ->download($tempFile, $fileName)
            ->deleteFileAfterSend(true);
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

        $user       = $questionSet->user;
        $schoolId   = $user?->school_id;
        $currentUser = auth()->user();

        // Cari template dengan prioritas:
        // 1. Template ID eksplisit jika ada
        // 2. Template default milik sekolah (school_id match)
        // 3. Template default milik user yang sedang login
        $template = null;

        if ($templateId) {
            $template = DocumentTemplate::find($templateId);
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

        if (! $template) {
            return back()->withErrors([
                'template' => 'Tidak ada template tersedia. Silakan upload template terlebih dahulu di menu Template Dokumen, atau gunakan export standar.',
            ]);
        }

        if (! Storage::disk('local')->exists($template->file_path)) {
            return back()->withErrors([
                'template' => 'File template tidak ditemukan di server. Silakan upload ulang template.',
            ]);
        }

        try {
            $outputPath = $service->generate(
                $template->file_path,
                $questionSet,
                includeAnswers: $type === 'guru'
            );
        } catch (\Exception $e) {
            \Log::error('Export template gagal: ' . $e->getMessage(), [
                'question_set_id' => $questionSet->id,
                'template_id'     => $template->id,
                'trace'           => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'template' => 'Gagal generate dokumen dari template: ' . $e->getMessage(),
            ]);
        }

        $fileName = $questionSet->title . ' - ' . ucfirst($type) . '.docx';

        return response()
            ->download($outputPath, $fileName)
            ->deleteFileAfterSend(true);
    }
}