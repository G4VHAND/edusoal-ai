<?php

namespace App\Http\Controllers;

use App\Models\QuestionSet;
use App\Services\Export\BankSoalExportService;
use Illuminate\Http\Request;

/**
 * Controller ini sengaja tipis — semua logic export (PDF, Word polos, Word
 * template + resolusi template mana yang dipakai) ada di BankSoalExportService
 * dan kelas pendukungnya (PlainWordExportService, DocumentTemplateResolver).
 * Tanggung jawab controller cuma: authorize, panggil service, kembalikan response.
 */
class BankSoalController extends Controller
{
    public function __construct(
        private readonly BankSoalExportService $exportService,
    ) {}

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

        return $this->exportService->guruPdf($questionSet);
    }

    public function exportStudentPdf(QuestionSet $questionSet)
    {
        $this->authorize('export', $questionSet);

        return $this->exportService->siswaPdf($questionSet);
    }

    public function exportStudentWord(QuestionSet $questionSet)
    {
        $this->authorize('export', $questionSet);

        return $this->exportService->siswaWord($questionSet);
    }

    public function exportWithTemplate(Request $request, QuestionSet $questionSet)
    {
        $this->authorize('export', $questionSet);

        return $this->exportService->withTemplate($request, $questionSet, auth()->user());
    }
}
