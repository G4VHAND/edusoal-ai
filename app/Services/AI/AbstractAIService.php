<?php

namespace App\Services\AI;

/**
 * Base class untuk semua AI provider service.
 */
abstract class AbstractAIService implements AIService
{
    private const BLOOM_MAP = [
        'mudah' => [
            'level'      => 'C1–C2 (Mengingat & Memahami)',
            'deskripsi'  => 'Siswa cukup mengingat fakta atau memahami konsep dasar.',
            'kata_kerja' => 'sebutkan, identifikasi, jelaskan, definisikan, klasifikasikan',
            'hindari'    => 'Hindari soal yang membutuhkan analisis, penghitungan multi-langkah, atau penalaran kompleks.',
        ],
        'sedang' => [
            'level'      => 'C3–C4 (Mengaplikasikan & Menganalisis)',
            'deskripsi'  => 'Siswa harus menerapkan konsep dalam situasi baru atau menganalisis hubungan antar konsep.',
            'kata_kerja' => 'hitung, terapkan, gunakan, bandingkan, analisis, simpulkan, bedakan',
            'hindari'    => 'Hindari soal hafalan murni. Soal harus membutuhkan langkah berpikir lebih dari satu.',
        ],
        'sulit' => [
            'level'      => 'C5–C6 (Mengevaluasi & Mencipta)',
            'deskripsi'  => 'Siswa harus mengevaluasi suatu situasi berdasarkan kriteria tertentu atau merancang solusi baru.',
            'kata_kerja' => 'nilai, kritisi, justifikasi, rancang, kembangkan, sintesis, prediksi',
            'hindari'    => 'Hindari soal yang bisa dijawab dengan hafalan atau pemahaman dasar saja.',
        ],
    ];

    private const JENJANG_MAP = [
        'Kelas 1 SD'  => ['usia' => '6-7 tahun',   'jenjang' => 'SD'],
        'Kelas 2 SD'  => ['usia' => '7-8 tahun',   'jenjang' => 'SD'],
        'Kelas 3 SD'  => ['usia' => '8-9 tahun',   'jenjang' => 'SD'],
        'Kelas 4 SD'  => ['usia' => '9-10 tahun',  'jenjang' => 'SD'],
        'Kelas 5 SD'  => ['usia' => '10-11 tahun', 'jenjang' => 'SD'],
        'Kelas 6 SD'  => ['usia' => '11-12 tahun', 'jenjang' => 'SD'],
        'Kelas 7 SMP' => ['usia' => '12-13 tahun', 'jenjang' => 'SMP'],
        'Kelas 8 SMP' => ['usia' => '13-14 tahun', 'jenjang' => 'SMP'],
        'Kelas 9 SMP' => ['usia' => '14-15 tahun', 'jenjang' => 'SMP'],
        'Kelas 10 SMA'=> ['usia' => '15-16 tahun', 'jenjang' => 'SMA'],
        'Kelas 11 SMA'=> ['usia' => '16-17 tahun', 'jenjang' => 'SMA'],
        'Kelas 12 SMA'=> ['usia' => '17-18 tahun', 'jenjang' => 'SMA'],
        'Kelas 10 SMK'=> ['usia' => '15-16 tahun', 'jenjang' => 'SMK'],
        'Kelas 11 SMK'=> ['usia' => '16-17 tahun', 'jenjang' => 'SMK'],
        'Kelas 12 SMK'=> ['usia' => '17-18 tahun', 'jenjang' => 'SMK'],
    ];

    protected function buildPrompt(array $data): string
    {
        $materialLimit   = config('ai.material_text_limit', 8000);
        $materialSection = '';
        $hasMaterial     = !empty($data['material_text']) || !empty($data['image_description']);

        // ── Gabungkan materi teks + deskripsi gambar ──────────────────────────
        $combinedMaterial = '';

        if (!empty($data['material_text'])) {
            $combinedMaterial .= mb_substr($data['material_text'], 0, $materialLimit);
        }

        if (!empty($data['image_description'])) {
            $combinedMaterial .= "\n\n[DESKRIPSI GAMBAR/VISUAL]\n" . $data['image_description'];
        }

        if ($combinedMaterial) {
            $materialSection = <<<MATERIAL

MATERI SUMBER (WAJIB DIGUNAKAN):
"""
{$combinedMaterial}
"""
MATERIAL;
        }

        // ── Instruksi anti-hallucination ──────────────────────────────────────
        $antiHallucination = $hasMaterial
            ? <<<ANTI
ATURAN KETAT — ANTI HALLUCINATION:
1. Setiap soal WAJIB dibuat berdasarkan materi sumber di atas.
2. Jangan menambahkan fakta, angka, nama, atau konsep yang TIDAK ada dalam materi sumber.
3. Jika informasi tidak cukup dalam materi untuk membuat soal, gunakan hanya yang tersedia.
4. Setiap soal WAJIB menyertakan field "source_paragraph" berisi kutipan singkat (1-2 kalimat) dari materi sumber yang menjadi dasar soal tersebut.
5. Jika soal berasal dari deskripsi gambar, isi source_paragraph dengan "[GAMBAR] " diikuti deskripsi singkat bagian gambar yang digunakan.
ANTI
            : <<<ANTI
ATURAN KETAT — AKURASI:
1. Gunakan hanya fakta yang sudah terbukti dan umum diketahui untuk mata pelajaran ini.
2. Jangan mengarang fakta, angka, atau nama yang tidak pasti kebenarannya.
3. Jika tidak yakin dengan suatu fakta, pilih topik lain yang lebih pasti.
4. Isi field "source_paragraph" dengan "Pengetahuan umum [mata pelajaran]" jika tidak ada materi sumber.
ANTI;

        $total   = $data['total_questions'];
        $subject = $data['subject'];
        $grade   = $data['grade'];
        $topic   = $data['topic'];
        $diff    = $data['difficulty'];

        $bloom   = self::BLOOM_MAP[$diff]    ?? self::BLOOM_MAP['sedang'];
        $jenjang = self::JENJANG_MAP[$grade] ?? null;

        $konteksJenjang = $jenjang
            ? "Siswa berusia {$jenjang['usia']} (jenjang {$jenjang['jenjang']}). "
              . "Gunakan kosakata, konteks, dan kompleksitas kalimat yang sesuai usia tersebut."
            : "Sesuaikan kosakata dan kompleksitas kalimat dengan jenjang {$grade}.";

        $bloomSection = <<<BLOOM

STANDAR KOGNITIF (Taksonomi Bloom — Kurikulum Merdeka):
- Level: {$bloom['level']}
- Deskripsi: {$bloom['deskripsi']}
- Gunakan kata kerja operasional: {$bloom['kata_kerja']}
- {$bloom['hindari']}

KONTEKS JENJANG:
- {$konteksJenjang}
BLOOM;

        if ($data['question_type'] === 'essay') {
            return <<<PROMPT
Buatkan {$total} soal essay dalam Bahasa Indonesia.

Detail:
- Mata pelajaran: {$subject}
- Kelas: {$grade}
- Topik: {$topic}
- Tingkat kesulitan: {$diff}
{$bloomSection}
{$antiHallucination}
{$materialSection}

PANDUAN GAMBAR:
- Jika soal membutuhkan gambar (diagram, grafik, peta, ilustrasi) agar bisa dijawab dengan benar, isi "needs_image": true dan "image_recommendation" dengan deskripsi singkat gambar yang dibutuhkan.
- Jika soal bisa dijawab tanpa gambar, isi "needs_image": false dan "image_recommendation": null.
- Contoh image_recommendation: "Diagram siklus air dengan label evaporasi, kondensasi, dan presipitasi"

Format jawaban wajib berupa JSON valid tanpa markdown, tanpa kode block:

{
  "questions": [
    {
      "question_text": "...",
      "correct_answer": "...",
      "explanation": "...",
      "source_paragraph": "Kutipan singkat dari materi sumber yang menjadi dasar soal ini.",
      "needs_image": false,
      "image_recommendation": null
    }
  ]
}
PROMPT;
        }

        return <<<PROMPT
Buatkan {$total} soal pilihan ganda dalam Bahasa Indonesia.

Detail:
- Mata pelajaran: {$subject}
- Kelas: {$grade}
- Topik: {$topic}
- Tingkat kesulitan: {$diff}
{$bloomSection}
{$antiHallucination}
{$materialSection}

PANDUAN GAMBAR:
- Jika soal membutuhkan gambar (diagram, grafik, peta, ilustrasi) agar bisa dijawab dengan benar, isi "needs_image": true dan "image_recommendation" dengan deskripsi singkat gambar yang dibutuhkan.
- Jika soal bisa dijawab tanpa gambar, isi "needs_image": false dan "image_recommendation": null.
- Contoh image_recommendation: "Diagram rangkaian listrik seri dengan 2 resistor dan 1 baterai"

Format jawaban wajib berupa JSON valid tanpa markdown, tanpa kode block:

{
  "questions": [
    {
      "question_text": "...",
      "option_a": "...",
      "option_b": "...",
      "option_c": "...",
      "option_d": "...",
      "correct_answer": "A",
      "explanation": "...",
      "source_paragraph": "Kutipan singkat dari materi sumber yang menjadi dasar soal ini.",
      "needs_image": false,
      "image_recommendation": null
    }
  ]
}
PROMPT;
    }

    protected function cleanText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return trim(str_replace(['**', '*', '```'], '', $text));
    }
}