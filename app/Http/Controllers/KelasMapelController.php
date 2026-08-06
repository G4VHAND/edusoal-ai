<?php

namespace App\Http\Controllers;

use App\Models\QuestionSet;

class KelasMapelController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $gradeCounts = QuestionSet::where('user_id', $userId)
            ->whereNotNull('grade')
            ->selectRaw('grade, COUNT(*) as total')
            ->groupBy('grade')
            ->pluck('total', 'grade');

        $subjectCounts = QuestionSet::where('user_id', $userId)
            ->whereNotNull('subject')
            ->selectRaw('subject, COUNT(*) as total')
            ->groupBy('subject')
            ->pluck('total', 'subject');

        $curriculum = config('curriculum');

        // Gabungkan daftar kelas standar dengan hitungan asli milik user —
        // kelas yang belum pernah dipakai tetap muncul dengan angka 0.
        $grades = collect($curriculum['grades'])->map(
            fn ($list, $level) => collect($list)->map(fn ($grade) => [
                'name' => $grade,
                'total' => $gradeCounts->get($grade, 0),
            ])
        );

        $subjects = collect($curriculum['subjects'])->map(fn ($subject) => [
            'name' => $subject,
            'total' => $subjectCounts->get($subject, 0),
        ]);

        // Subjek yang dipakai user tapi tidak ada di daftar standar (karena
        // field subject di form Generate Soal itu free-text) — tetap
        // ditampilkan supaya datanya tidak "hilang" dari halaman ini.
        $customSubjects = $subjectCounts->keys()
            ->diff($curriculum['subjects'])
            ->map(fn ($subject) => ['name' => $subject, 'total' => $subjectCounts->get($subject)]);

        return view('kelas-mapel.index', compact('grades', 'subjects', 'customSubjects'));
    }
}
