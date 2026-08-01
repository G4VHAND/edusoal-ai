<?php

namespace Tests\Feature\BankSoal;

use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankSoalExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'role' => 'individual',
            'quota_used_this_month' => 0,
            'quota_reset_at' => now()->addMonth(),
            'is_active' => true,
        ], $attrs));
    }

    private function makeQuestionSet(User $user, array $attrs = []): QuestionSet
    {
        return QuestionSet::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Test Bank Soal',
            'subject' => 'Matematika',
            'grade' => 'Kelas 9 SMP',
            'topic' => 'Aljabar',
            'question_type' => 'multiple_choice',
            'difficulty' => 'sedang',
            'total_questions' => 1,
            'ai_provider' => 'gemini',
            'status' => 'completed',
            'is_ai_generated' => true,
        ], $attrs));
    }

    // ── Export otomatis fallback ke format standar bila tidak ada template ──
    // (Bagian dari perbaikan #9 — guru tidak boleh melihat error "upload
    // template dulu", export harus tetap berhasil download.)

    public function test_export_with_template_falls_back_to_standard_format_when_no_template_exists(): void
    {
        $user = $this->makeUser();
        $questionSet = $this->makeQuestionSet($user);
        Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Berapa 2 + 2?',
            'option_a' => '3', 'option_b' => '4', 'option_c' => '5', 'option_d' => '6',
            'correct_answer' => 'B',
        ]);

        $response = $this->actingAs($user)
            ->get("/bank-soal/{$questionSet->id}/export-template?type=guru");

        // Tidak boleh error/redirect — harus langsung jadi file download,
        // bukan balik ke halaman sebelumnya dengan pesan error.
        $response->assertOk();
        $this->assertStringContainsString(
            '.docx',
            $response->headers->get('content-disposition') ?? ''
        );
    }

    // ── Format bold & baris baru tersimpan & tampil dengan benar ────────────
    // (Bagian dari perbaikan #4.)

    public function test_question_detail_page_renders_bold_marker_as_html_strong(): void
    {
        $user = $this->makeUser();
        $questionSet = $this->makeQuestionSet($user);
        Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Perhatikan **rumus luas persegi** berikut ini.',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'A',
        ]);

        $response = $this->actingAs($user)->get("/bank-soal/{$questionSet->id}");

        $response->assertOk();
        $response->assertSee('<strong>rumus luas persegi</strong>', false);
        // Penanda mentah '**' tidak boleh bocor ke tampilan.
        $response->assertDontSee('**rumus luas persegi**');
        // Nomor soal tetap tebal...
        $response->assertSee('<strong>1.</strong>', false);
        // ...tapi paragraf soal TIDAK lagi dipaksa tebal semua (supaya kata
        // yang ditandai AI benar-benar menonjol, bukan tenggelam).
        $response->assertDontSee('class="font-bold text-slate-900 text-justify"', false);
    }

    public function test_export_pdf_renders_bold_marker_as_html_strong(): void
    {
        $user = $this->makeUser();
        $questionSet = $this->makeQuestionSet($user);
        Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Apa itu **fotosintesis**?',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'A',
        ]);

        $response = $this->actingAs($user)->get("/bank-soal/{$questionSet->id}/export-pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
    // ── Poin #1: rapikan detail soal ─────────────────────────────────────────

    public function test_correct_option_is_visually_highlighted_in_options_grid(): void
    {
        $user = $this->makeUser();
        $questionSet = $this->makeQuestionSet($user);
        Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Pertanyaan uji.',
            'option_a' => 'Opsi A', 'option_b' => 'Opsi B', 'option_c' => 'Opsi C', 'option_d' => 'Opsi D',
            'correct_answer' => 'C',
        ]);

        $response = $this->actingAs($user)->get("/bank-soal/{$questionSet->id}");

        $response->assertOk();
        // Opsi C harus dapat kelas highlight hijau, opsi lain tidak.
        $response->assertSee('bg-green-50 border-green-300', false);
    }

    public function test_detail_page_no_longer_shows_duplicate_daftar_pustaka_section(): void
    {
        $user = $this->makeUser();
        $questionSet = $this->makeQuestionSet($user, [
            'source_reference' => 'Buku Paket Matematika Kelas 9 Kurikulum Merdeka',
        ]);
        Question::create([
            'question_set_id' => $questionSet->id,
            'question_text' => 'Pertanyaan uji.',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'A',
        ]);

        $response = $this->actingAs($user)->get("/bank-soal/{$questionSet->id}");

        $response->assertOk();
        // Referensi tetap muncul SATU KALI (di kartu "Referensi Utama"),
        // section "Daftar Pustaka" yang duplikat di bawah sudah dihapus.
        $response->assertDontSee('Daftar Pustaka');
        $response->assertSee('Referensi Utama');
    }
}
