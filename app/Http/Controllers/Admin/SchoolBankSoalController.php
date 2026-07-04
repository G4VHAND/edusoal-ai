<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionSet;
use App\Models\User;

/**
 * School Admin — lihat semua bank soal guru di sekolahnya.
 */
class SchoolBankSoalController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Super admin bisa lihat semua, school admin hanya sekolahnya
        $query = QuestionSet::with(['user'])
            ->when($user->isSchoolAdmin(), function ($q) use ($user) {
                // Hanya bank soal dari guru di sekolah yang sama
                $q->whereHas('user', fn ($u) => $u->where('school_id', $user->school_id));
            })
            ->latest();

        $search = request('search');
        $teacherId = request('teacher_id');
        $questionType = request('question_type');
        $difficulty = request('difficulty');

        $query
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%");
            }))
            ->when($teacherId, fn ($q) => $q->where('user_id', $teacherId))
            ->when($questionType, fn ($q) => $q->where('question_type', $questionType))
            ->when($difficulty, fn ($q) => $q->where('difficulty', $difficulty));

        $questionSets = $query->paginate(20)->withQueryString();

        // Daftar guru untuk filter
        $teachers = User::where('role', 'teacher')
            ->when($user->isSchoolAdmin(), fn ($q) => $q->where('school_id', $user->school_id))
            ->orderBy('name')
            ->get();

        // Statistik ringkas
        $stats = [
            'total' => $query->count(),
            'teachers' => $teachers->count(),
        ];

        return view('admin.bank-soal.index', compact(
            'questionSets', 'teachers', 'stats',
            'search', 'teacherId', 'questionType', 'difficulty'
        ));
    }

    public function show(QuestionSet $questionSet)
    {
        $user = auth()->user();

        // School admin hanya bisa lihat soal dari gurunya sendiri
        if ($user->isSchoolAdmin()) {
            $teacherSchoolId = $questionSet->user?->school_id;
            if ($teacherSchoolId !== $user->school_id) {
                abort(403);
            }
        }

        $questionSet->load('questions', 'user');

        return view('admin.bank-soal.show', compact('questionSet'));
    }
}
