<?php

namespace Tests\Unit\Document;

use App\Services\Document\TextFormatter;
use PhpOffice\PhpWord\Element\TextRun;
use Tests\TestCase;

class TextFormatterTest extends TestCase
{
    // ── toHtml ───────────────────────────────────────────────────────────────

    public function test_to_html_converts_bold_markers(): void
    {
        $html = TextFormatter::toHtml('Ini **penting** sekali');

        $this->assertEquals('Ini <strong>penting</strong> sekali', $html);
    }

    public function test_to_html_converts_newlines_to_br(): void
    {
        $html = TextFormatter::toHtml("Baris satu\nBaris dua");

        $this->assertEquals('Baris satu<br>Baris dua', $html);
    }

    public function test_to_html_handles_bold_and_newline_together(): void
    {
        $html = TextFormatter::toHtml("Soal **utama**\nSub pertanyaan **b**");

        $this->assertEquals('Soal <strong>utama</strong><br>Sub pertanyaan <strong>b</strong>', $html);
    }

    public function test_to_html_escapes_html_special_characters(): void
    {
        $html = TextFormatter::toHtml('Nilai x < 5 & y > 3');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;', $html);
        $this->assertStringContainsString('&gt;', $html);
        $this->assertStringContainsString('&amp;', $html);
    }

    public function test_to_html_returns_empty_string_for_null(): void
    {
        $this->assertEquals('', TextFormatter::toHtml(null));
    }

    public function test_to_html_returns_empty_string_for_empty_string(): void
    {
        $this->assertEquals('', TextFormatter::toHtml(''));
    }

    public function test_to_html_handles_multiple_bold_segments_in_one_line(): void
    {
        $html = TextFormatter::toHtml('**A** biasa **B**');

        $this->assertEquals('<strong>A</strong> biasa <strong>B</strong>', $html);
    }

    // ── toTextRun (dipakai untuk setComplexValue di template Word) ──────────

    public function test_to_text_run_returns_text_run_instance(): void
    {
        $run = TextFormatter::toTextRun('Halo **dunia**');

        $this->assertInstanceOf(TextRun::class, $run);
    }

    public function test_to_text_run_creates_separate_elements_for_bold_and_normal_text(): void
    {
        $run = TextFormatter::toTextRun('Biasa **tebal**');

        // "Biasa " dan "tebal" harus jadi 2 elemen Text terpisah di dalam
        // TextRun (supaya salah satunya bisa diberi style bold), bukan
        // digabung jadi satu string polos.
        $this->assertCount(2, $run->getElements());
    }

    public function test_to_text_run_adds_line_break_between_lines(): void
    {
        $run = TextFormatter::toTextRun("Baris satu\nBaris dua");

        // 1 elemen Text (baris satu) + 1 TextBreak + 1 elemen Text (baris dua)
        $this->assertCount(3, $run->getElements());
    }

    // ── applyToContainer (dipakai di export Word format standar) ─────────────

    public function test_apply_to_container_keeps_mixed_bold_and_plain_segments_on_one_paragraph(): void
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord;
        $section = $phpWord->addSection();

        TextFormatter::applyToContainer($section, 'Jawaban: **A**');

        // Harus jadi TEPAT 1 paragraph (1 TextRun) untuk 1 baris teks,
        // walau isinya campuran segmen biasa + bold — bukan pecah jadi
        // beberapa paragraph terpisah seperti kalau addText() dipanggil
        // langsung per segmen ke Section.
        $this->assertCount(1, $section->getElements());
        $this->assertInstanceOf(TextRun::class, $section->getElements()[0]);
        $this->assertCount(2, $section->getElements()[0]->getElements());
    }

    public function test_apply_to_container_creates_one_paragraph_per_line(): void
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord;
        $section = $phpWord->addSection();

        TextFormatter::applyToContainer($section, "Baris satu\nBaris dua\nBaris tiga");

        $this->assertCount(3, $section->getElements());
    }

    public function test_apply_to_container_applies_justify_paragraph_style(): void
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord;
        $section = $phpWord->addSection();

        TextFormatter::applyToContainer(
            $section,
            'Soal yang cukup panjang untuk diratakan kiri-kanan.',
            [],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]
        );

        $paragraph = $section->getElements()[0];
        $this->assertEquals(
            \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
            $paragraph->getParagraphStyle()->getAlignment()
        );
    }

    public function test_apply_to_container_prefix_stays_on_same_line_without_forcing_body_bold(): void
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord;
        $section = $phpWord->addSection();

        // Nomor soal ("1. ") harus tebal, tapi body-nya TIDAK ikut tebal
        // kecuali memang ditandai **bold** oleh AI — supaya penekanan dari
        // AI tetap terlihat menonjol dibanding teks sekitarnya.
        TextFormatter::applyToContainer(
            $section,
            'Soal biasa dengan **kata penting**.',
            [],
            [],
            ['text' => '1. ', 'style' => ['bold' => true]]
        );

        $paragraph = $section->getElements()[0];
        $elements = $paragraph->getElements();

        // 1 elemen prefix ("1. ") + 3 elemen body: "Soal biasa dengan "
        // (biasa) + "kata penting" (bold) + "." (biasa lagi, karena titik
        // di luar tanda **...** jadi segmen tersendiri).
        $this->assertCount(4, $elements);
    }
}
