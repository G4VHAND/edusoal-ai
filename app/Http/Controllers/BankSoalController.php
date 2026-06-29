<?php

namespace App\Http\Controllers;

use App\Models\QuestionSet;
use Barryvdh\DomPDF\Facade\Pdf;
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
}