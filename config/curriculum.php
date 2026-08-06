<?php

/**
 * Daftar kelas & mata pelajaran standar (SD-SMK, kurikulum umum Indonesia).
 * Ini daftar TETAP (bukan CRUD user) — dipakai untuk halaman referensi
 * "Kelas & Mapel" dan idealnya sinkron dengan pilihan di form Generate Soal
 * (lihat resources/views/question_sets/create.blade.php, select 'grade').
 *
 * Kalau daftar kelas di form Generate Soal berubah, update juga di sini
 * supaya statistik "Kelas & Mapel" tetap akurat.
 */
return [
    'grades' => [
        'SD' => ['Kelas 1 SD', 'Kelas 2 SD', 'Kelas 3 SD', 'Kelas 4 SD', 'Kelas 5 SD', 'Kelas 6 SD'],
        'SMP' => ['Kelas 7 SMP', 'Kelas 8 SMP', 'Kelas 9 SMP'],
        'SMA' => ['Kelas 10 SMA', 'Kelas 11 SMA', 'Kelas 12 SMA'],
        'SMK' => ['Kelas 10 SMK', 'Kelas 11 SMK', 'Kelas 12 SMK'],
    ],

    'subjects' => [
        'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'IPA', 'IPS',
        'Fisika', 'Kimia', 'Biologi', 'Sejarah', 'Geografi', 'Ekonomi',
        'PPKn', 'Pendidikan Agama', 'Seni Budaya', 'PJOK', 'Prakarya',
        'Informatika/TIK',
    ],
];
