<?php

namespace Tests\Unit\Document;

use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use App\Services\Document\PlainWordExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpWord\IOFactory;
use Tests\TestCase;

class PlainWordExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlainWordExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlainWordExportService;
    }

    private function makeQuestionSetWithQuestion(array $questionAttrs = []): QuestionSet
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $questionSet = QuestionSet::create([
            'user_id' => $user->id,
            'title' => 'Test Export',
            'subject' => 'Biologi',
            'grade' => 'Kelas 11 SMA',
            'topic' => 'Sistem Sirkulasi',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'total_questions' => 1,
            'status' => 'completed',
        ]);

        Question::create(array_merge([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Apa fungsi jantung?',
            'option_a' => 'Memompa darah',
            'option_b' => 'Mencerna makanan',
            'option_c' => 'Menyaring udara',
            'option_d' => 'Menghasilkan hormon',
            'correct_answer' => 'A',
            'explanation' => 'Jantung memompa darah ke seluruh tubuh.',
        ], $questionAttrs));

        return $questionSet->load('questions');
    }

    public function test_build_returns_path_to_an_actual_docx_file(): void
    {
        $questionSet = $this->makeQuestionSetWithQuestion();

        $path = $this->service->build($questionSet, includeAnswers: true);

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));

        // Harus benar-benar bisa dibaca ulang sebagai dokumen Word valid,
        // bukan cuma file kosong/rusak.
        $reloaded = IOFactory::load($path, 'Word2007');
        $this->assertNotEmpty($reloaded->getSections());

        @unlink($path);
    }

    public function test_include_answers_false_produces_smaller_file_without_answer_section(): void
    {
        // Tidak bisa membaca teks polos dari .docx dengan mudah tanpa
        // parsing XML manual, jadi kita pakai proxy yang masuk akal:
        // versi tanpa jawaban+pembahasan harus menghasilkan file yang
        // lebih kecil (karena kontennya lebih sedikit).
        $questionSet = $this->makeQuestionSetWithQuestion();

        $withAnswers = $this->service->build($questionSet, includeAnswers: true);
        $withoutAnswers = $this->service->build($questionSet, includeAnswers: false);

        $this->assertLessThan(filesize($withAnswers), filesize($withoutAnswers));

        @unlink($withAnswers);
        @unlink($withoutAnswers);
    }

    public function test_build_handles_essay_question_without_options(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $questionSet = QuestionSet::create([
            'user_id' => $user->id,
            'title' => 'Test Essay',
            'subject' => 'Biologi',
            'grade' => 'Kelas 11 SMA',
            'topic' => 'Sistem Sirkulasi',
            'question_type' => 'essay',
            'difficulty' => 'sedang',
            'total_questions' => 1,
            'status' => 'completed',
        ]);

        Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Jelaskan fungsi jantung.',
            'correct_answer' => 'Jantung memompa darah ke seluruh tubuh.',
        ]);

        $path = $this->service->build($questionSet->load('questions'), includeAnswers: true);

        $this->assertFileExists($path);
        @unlink($path);
    }
}
