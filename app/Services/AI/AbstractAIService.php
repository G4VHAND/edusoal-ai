<?php

namespace App\Services\AI;

/**
 * Base class untuk semua AI provider service.
 */
abstract class AbstractAIService implements AIService
{
    private const BLOOM_MAP = [
        'mudah' => [
            'level' => 'C1–C2 (Mengingat & Memahami)',
            'deskripsi' => 'Siswa cukup mengingat fakta atau memahami konsep dasar.',
            'kata_kerja' => 'sebutkan, identifikasi, jelaskan, definisikan, klasifikasikan',
            'hindari' => 'Hindari soal yang membutuhkan analisis, penghitungan multi-langkah, penalaran kompleks, atau aplikasi konsep ke situasi baru. Soal hanya boleh meminta siswa mengingat fakta atau menjelaskan definisi/konsep dasar — JANGAN meminta siswa memberi contoh penerapan dalam kehidupan nyata, karena itu termasuk level C3.',
        ],
        'sedang' => [
            'level' => 'C3–C4 (Mengaplikasikan & Menganalisis)',
            'deskripsi' => 'Siswa harus menerapkan konsep dalam situasi baru atau menganalisis hubungan antar konsep.',
            'kata_kerja' => 'hitung, terapkan, gunakan, bandingkan, analisis, simpulkan, bedakan',
            'hindari' => 'Hindari soal hafalan murni. Soal harus membutuhkan langkah berpikir lebih dari satu.',
        ],
        'sulit' => [
            'level' => 'C5–C6 (Mengevaluasi & Mencipta)',
            'deskripsi' => 'Siswa harus mengevaluasi suatu situasi berdasarkan kriteria tertentu atau merancang solusi baru.',
            'kata_kerja' => 'nilai, kritisi, justifikasi, rancang, kembangkan, sintesis, prediksi',
            'hindari' => 'Hindari soal yang bisa dijawab dengan hafalan atau pemahaman dasar saja.',
        ],
    ];

    private const JENJANG_MAP = [
        'Kelas 1 SD' => ['usia' => '6-7 tahun',   'jenjang' => 'SD'],
        'Kelas 2 SD' => ['usia' => '7-8 tahun',   'jenjang' => 'SD'],
        'Kelas 3 SD' => ['usia' => '8-9 tahun',   'jenjang' => 'SD'],
        'Kelas 4 SD' => ['usia' => '9-10 tahun',  'jenjang' => 'SD'],
        'Kelas 5 SD' => ['usia' => '10-11 tahun', 'jenjang' => 'SD'],
        'Kelas 6 SD' => ['usia' => '11-12 tahun', 'jenjang' => 'SD'],
        'Kelas 7 SMP' => ['usia' => '12-13 tahun', 'jenjang' => 'SMP'],
        'Kelas 8 SMP' => ['usia' => '13-14 tahun', 'jenjang' => 'SMP'],
        'Kelas 9 SMP' => ['usia' => '14-15 tahun', 'jenjang' => 'SMP'],
        'Kelas 10 SMA' => ['usia' => '15-16 tahun', 'jenjang' => 'SMA'],
        'Kelas 11 SMA' => ['usia' => '16-17 tahun', 'jenjang' => 'SMA'],
        'Kelas 12 SMA' => ['usia' => '17-18 tahun', 'jenjang' => 'SMA'],
        'Kelas 10 SMK' => ['usia' => '15-16 tahun', 'jenjang' => 'SMK'],
        'Kelas 11 SMK' => ['usia' => '16-17 tahun', 'jenjang' => 'SMK'],
        'Kelas 12 SMK' => ['usia' => '17-18 tahun', 'jenjang' => 'SMK'],
    ];

    /**
     * Mapping kurikulum ke instruksi spesifik untuk AI.
     */
    private const CURRICULUM_MAP = [
        'merdeka' => [
            'nama' => 'Kurikulum Merdeka',
            'instruksi' => 'Soal harus mengacu pada Capaian Pembelajaran (CP) dan berorientasi pada profil pelajar Pancasila. '
                .'Fokus pada pemahaman bermakna (deep understanding) dan kontekstual, bukan hafalan semata. '
                .'Gunakan pendekatan diferensiasi yang relevan dengan fase perkembangan siswa.',
        ],
        'k13' => [
            'nama' => 'Kurikulum 2013 (K13)',
            'instruksi' => 'Soal harus mengacu pada Kompetensi Dasar (KD) dan Indikator Pencapaian Kompetensi (IPK). '
                .'Sertakan keseimbangan antara aspek pengetahuan (KI-3) dan keterampilan (KI-4). '
                .'Gunakan kata kerja operasional sesuai Permendikbud yang berlaku untuk K13.',
        ],
    ];

    /**
     * Mapping jenis asesmen ke instruksi spesifik untuk AI.
     */
    private const ASSESSMENT_MAP = [
        'reguler' => [
            'nama' => 'Reguler',
            'instruksi' => '',
        ],
        'hots' => [
            'nama' => 'HOTS (Higher Order Thinking Skills)',
            'instruksi' => "\n\nKHUSUS HOTS — ATURAN TAMBAHAN:\n"
                ."- Soal WAJIB berbasis stimulus (wacana, data, grafik, kasus, atau ilustrasi situasi nyata).\n"
                ."- Hindari soal yang bisa dijawab langsung tanpa membaca/menganalisis stimulus.\n"
                ."- Soal harus menuntut siswa menganalisis, mengevaluasi, atau memecahkan masalah kontekstual.\n"
                .'- Sertakan stimulus/wacana singkat di awal question_text sebelum pertanyaan inti.',
        ],
        'akm' => [
            'nama' => 'AKM (Asesmen Kompetensi Minimum)',
            'instruksi' => "\n\nKHUSUS AKM — ATURAN TAMBAHAN:\n"
                ."- Soal mengikuti format AKM Kemendikbud: literasi membaca atau numerasi.\n"
                ."- Gunakan konteks personal, sosial budaya, atau saintifik yang relevan dengan kehidupan siswa.\n"
                ."- Untuk numerasi: sertakan data, tabel, atau grafik yang perlu diinterpretasi.\n"
                ."- Untuk literasi: sertakan teks bacaan singkat (100-200 kata) sebelum soal.\n"
                .'- Hindari soal hafalan rumus atau fakta murni — fokus pada penalaran dan aplikasi.',
        ],
    ];

    protected function buildPrompt(array $data): string
    {
        $materialLimit = config('ai.material_text_limit', 8000);
        $materialSection = '';
        $hasMaterial = ! empty($data['material_text']) || ! empty($data['image_description']);

        // ── Gabungkan materi teks + deskripsi gambar ──────────────────────────
        $combinedMaterial = '';

        if (! empty($data['material_text'])) {
            $combinedMaterial .= mb_substr($data['material_text'], 0, $materialLimit);
        }

        if (! empty($data['image_description'])) {
            $combinedMaterial .= "\n\n[DESKRIPSI GAMBAR/VISUAL]\n".$data['image_description'];
        }

        if ($combinedMaterial) {
            $materialSection = <<<MATERIAL

MATERI SUMBER (WAJIB DIGUNAKAN):
"""
{$combinedMaterial}
"""
MATERIAL;
        }

        $total = $data['total_questions'];
        $subject = $data['subject'];
        $grade = $data['grade'];
        $topic = $data['topic'];
        $diff = $data['difficulty'];
        $curriculum = $data['curriculum'] ?? 'merdeka';
        $assessment = $data['assessment_type'] ?? 'reguler';

        $bloom = self::BLOOM_MAP[$diff] ?? self::BLOOM_MAP['sedang'];
        $jenjang = self::JENJANG_MAP[$grade] ?? null;
        $currInfo = self::CURRICULUM_MAP[$curriculum] ?? self::CURRICULUM_MAP['merdeka'];
        $assessInfo = self::ASSESSMENT_MAP[$assessment] ?? self::ASSESSMENT_MAP['reguler'];
        $curriculumName = $currInfo['nama'];

        // ── Contoh nilai di JSON template ────────────────────────────────────
        // PENTING: AI cenderung meniru pola dari CONTOH KONKRET di JSON
        // template lebih kuat daripada instruksi prosa di atasnya. Kalau
        // contohnya statis/generik ("Kutipan singkat dari materi sumber..."),
        // AI akan ikut generik walau instruksinya sudah bilang "boleh jurnal/
        // ebook/video" — makanya contohnya HARUS ikut berubah sesuai kondisi.
        $sourceReferenceExample = $hasMaterial
            ? 'Materi/dokumen yang diunggah pengguna.'
            : 'Jurnal Pendidikan Sains Indonesia — topik terkait, ATAU Video pembelajaran YouTube channel edukasi, ATAU Buku Paket sesuai mapel & kelas (isi dengan yang paling relevan dan kamu yakini, JANGAN salin literal contoh ini)';

        $sourceParagraphExample = $hasMaterial
            ? 'Kutipan singkat (1-2 kalimat) dari materi sumber yang menjadi dasar soal ini.'
            : "Nama sumber konkret yang relevan dengan JAWABAN soal ini spesifik — misal nama jurnal/ebook/artikel/video yang relevan (JANGAN salin literal contoh ini, JANGAN tulis 'Kutipan singkat dari materi sumber' karena tidak ada materi yang diupload)";

        // ── Instruksi anti-hallucination ──────────────────────────────────────
        $antiHallucination = $hasMaterial
            ? <<<'ANTI'
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
4. Setiap soal WAJIB menyertakan field "source_paragraph" berisi SATU jenis referensi konkret yang relevan dengan jawaban soal tersebut — boleh berupa nama buku/modul, jurnal ilmiah, artikel/website edukasi, atau video pembelajaran (mis. YouTube) yang kamu yakini benar-benar relevan dan kredibel untuk topik ini.
5. JANGAN menulis link/URL persis, DOI, atau nomor volume jurnal — kamu tidak bisa memverifikasi apakah itu benar-benar ada, jadi cukup sebutkan NAMA/JUDUL sumbernya saja (mis. "Jurnal Pendidikan Fisika Indonesia — topik Hukum Newton", bukan URL atau tautan buatan).
6. Kalau benar-benar tidak ada nama sumber spesifik yang kamu yakini, isi dengan format: "Konsep dasar {$subject} tingkat {$grade}" — JANGAN gunakan frasa generik seperti "Pengetahuan umum" tanpa keterangan lebih lanjut.
ANTI;

        // ── Sumber referensi (level SET soal, bukan per soal) ─────────────────
        // Beda dengan "source_paragraph" (kutipan per soal dari materi upload),
        // ini adalah SATU referensi untuk keseluruhan set soal — supaya guru
        // tahu buku/kurikulum apa yang jadi acuan, bukan cuma "pengetahuan umum".
        $sourceReferenceSection = $hasMaterial
            ? <<<'SRCREF'
SUMBER REFERENSI (WAJIB, SATU untuk keseluruhan set soal — bukan per soal):
- Isi field "source_reference" (di level atas JSON, BUKAN di dalam array "questions") dengan: "Materi/dokumen yang diunggah pengguna."
SRCREF
            : <<<SRCREF
SUMBER REFERENSI (WAJIB, SATU untuk keseluruhan set soal — bukan per soal):
- Isi field "source_reference" (di level atas JSON, BUKAN di dalam array "questions") dengan referensi KONKRET yang relevan dengan topik soal ini. Boleh berupa salah satu jenis berikut, pilih yang paling sesuai:
  a) Buku teks/buku paket yang lazim dipakai untuk mata pelajaran, kelas, dan kurikulum ini, ATAU acuan kurikulum resmi (mis. Capaian Pembelajaran).
  b) Jurnal ilmiah atau artikel akademik yang relevan dengan topik.
  c) Ebook atau modul pembelajaran daring yang kredibel (mis. dari platform edukasi yang dikenal).
  d) Artikel/situs web edukasi yang kredibel (mis. ensiklopedia pendidikan, situs kementerian, dsb.).
  e) Video pembelajaran (mis. channel YouTube edukasi) yang relevan dengan topik.
- JANGAN hanya menulis "pengetahuan umum" atau kalimat generik serupa — sebutkan NAMA/JUDUL sumbernya secara konkret, misalnya: "Buku Paket Biologi Kelas 11 Kurikulum Merdeka (Kemdikbud)", "Jurnal Pendidikan Sains Indonesia — topik Sistem Peredaran Darah", atau "Video pembelajaran YouTube channel edukasi tentang Hukum Newton".
- JANGAN menulis link/URL, DOI, nomor volume jurnal, atau tautan video secara persis — kamu tidak bisa memastikan itu benar-benar ada. Cukup sebutkan NAMA/JUDUL/JENIS sumbernya saja, biarkan pengguna yang mencarinya sendiri.
- Kalau benar-benar tidak ada nama sumber spesifik yang kamu yakini kebenarannya, gunakan format seperti: "Konsep dasar {$subject} tingkat {$grade} sesuai {$curriculumName}" (isikan dengan mata pelajaran, kelas, dan kurikulum yang sesungguhnya, JANGAN salin literal template ini).
SRCREF;

        $konteksJenjang = $jenjang
            ? "Siswa berusia {$jenjang['usia']} (jenjang {$jenjang['jenjang']}). "
              .'Gunakan kosakata, konteks, dan kompleksitas kalimat yang sesuai usia tersebut.'
            : "Sesuaikan kosakata dan kompleksitas kalimat dengan jenjang {$grade}.";

        $bloomSection = <<<BLOOM

STANDAR KOGNITIF (Taksonomi Bloom):
- Level: {$bloom['level']}
- Deskripsi: {$bloom['deskripsi']}
- Gunakan kata kerja operasional: {$bloom['kata_kerja']}
- {$bloom['hindari']}
- PENTING: SEMUA soal dalam satu set harus berada pada level kognitif yang SAMA ({$bloom['level']}). Jangan membuat sebagian soal lebih sulit atau lebih mudah dari level yang ditentukan.

KURIKULUM: {$currInfo['nama']}
- {$currInfo['instruksi']}

JENIS ASESMEN: {$assessInfo['nama']}{$assessInfo['instruksi']}

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
{$sourceReferenceSection}
{$materialSection}

PANDUAN GAMBAR:
- Jika soal membutuhkan gambar (diagram, grafik, peta, ilustrasi) agar bisa dijawab dengan benar, isi "needs_image": true dan "image_recommendation" dengan deskripsi singkat gambar yang dibutuhkan.
- Jika soal bisa dijawab tanpa gambar, isi "needs_image": false dan "image_recommendation": null.
- Contoh image_recommendation: "Diagram siklus air dengan label evaporasi, kondensasi, dan presipitasi"

Format jawaban wajib berupa JSON valid tanpa markdown, tanpa kode block:

{
  "source_reference": "{$sourceReferenceExample}",
  "questions": [
    {
      "question_text": "...",
      "correct_answer": "...",
      "explanation": "...",
      "source_paragraph": "{$sourceParagraphExample}",
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
{$sourceReferenceSection}
{$materialSection}

PANDUAN GAMBAR:
- Jika soal membutuhkan gambar (diagram, grafik, peta, ilustrasi) agar bisa dijawab dengan benar, isi "needs_image": true dan "image_recommendation" dengan deskripsi singkat gambar yang dibutuhkan.
- Jika soal bisa dijawab tanpa gambar, isi "needs_image": false dan "image_recommendation": null.
- Contoh image_recommendation: "Diagram rangkaian listrik seri dengan 2 resistor dan 1 baterai"

Format jawaban wajib berupa JSON valid tanpa markdown, tanpa kode block:

{
  "source_reference": "{$sourceReferenceExample}",
  "questions": [
    {
      "question_text": "...",
      "option_a": "...",
      "option_b": "...",
      "option_c": "...",
      "option_d": "...",
      "correct_answer": "A",
      "explanation": "...",
      "source_paragraph": "{$sourceParagraphExample}",
      "needs_image": false,
      "image_recommendation": null
    }
  ]
}
PROMPT;
    }
}
