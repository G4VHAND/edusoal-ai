<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\IndividualUserController;
use App\Http\Controllers\Admin\SchoolAIProviderController;
use App\Http\Controllers\Admin\SchoolBankSoalController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\SchoolLetterheadController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\BankSoalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionSetController;
use Illuminate\Support\Facades\Route;

// ─── Public ───────────────────────────────────────────────────────────────────
Route::get('/', [LandingController::class, 'index']);

// ─── Dashboard Guru/Individual ────────────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ─── Bank Soal ────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/bank-soal', [BankSoalController::class, 'index'])
        ->name('bank-soal');

    Route::get('/bank-soal/{questionSet}', [QuestionSetController::class, 'show'])
        ->name('bank-soal.show');

    Route::get('/bank-soal/{questionSet}/status', [QuestionSetController::class, 'status'])
        ->name('bank-soal.status');

    // Gambar per soal
    Route::post('/bank-soal/{questionSet}/questions/{question}/image', [QuestionSetController::class, 'uploadQuestionImage'])
        ->name('bank-soal.question.image.upload');

    Route::delete('/bank-soal/{questionSet}/questions/{question}/image', [QuestionSetController::class, 'deleteQuestionImage'])
        ->name('bank-soal.question.image.delete');

    Route::delete('/bank-soal/{questionSet}/questions/{question}', [QuestionSetController::class, 'destroyQuestion'])
        ->name('bank-soal.question.destroy');

    Route::get('/bank-soal/{questionSet}/questions/{question}/image', [QuestionSetController::class, 'serveQuestionImage'])
        ->name('bank-soal.question.image.serve');

    Route::get('/bank-soal/{questionSet}/edit', [QuestionSetController::class, 'edit'])
        ->name('bank-soal.edit');

    Route::put('/bank-soal/{questionSet}', [QuestionSetController::class, 'update'])
        ->name('bank-soal.update');

    Route::delete('/bank-soal/{questionSet}', [QuestionSetController::class, 'destroy'])
        ->name('bank-soal.destroy');

    Route::get('/bank-soal/{questionSet}/export-pdf', [BankSoalController::class, 'exportPdf'])
        ->name('bank-soal.export-pdf');

    Route::get('/bank-soal/{questionSet}/export-student-pdf', [BankSoalController::class, 'exportStudentPdf'])
        ->name('bank-soal.export-student-pdf');

    Route::get('/bank-soal/{questionSet}/export-student-word', [BankSoalController::class, 'exportStudentWord'])
        ->name('bank-soal.export-student-word');

    Route::get('/bank-soal/{questionSet}/export-template', [BankSoalController::class, 'exportWithTemplate'])
        ->name('bank-soal.export-template');

});

// ─── Template Dokumen ──────────────────────────────────────────────────────────
// Hanya untuk admin sekolah (template berlaku untuk semua guru di sekolahnya)
// dan user individual (template personal, karena tidak ada admin sekolah yang
// mengelola untuk mereka). Guru TIDAK memiliki akses ke sini — saat export,
// guru otomatis memakai template default sekolahnya tanpa perlu tahu soal
// pengelolaan template (lihat BankSoalController::exportWithTemplate).
Route::middleware(['auth', 'verified', 'role:school_admin,individual'])->group(function () {

    Route::get('/templates', [DocumentTemplateController::class, 'index'])
        ->name('templates.index');

    Route::get('/templates/create', [DocumentTemplateController::class, 'create'])
        ->name('templates.create');

    Route::post('/templates', [DocumentTemplateController::class, 'store'])
        ->name('templates.store');

    Route::delete('/templates/{template}', [DocumentTemplateController::class, 'destroy'])
        ->name('templates.destroy');

    Route::patch('/templates/{template}/set-default', [DocumentTemplateController::class, 'setDefault'])
        ->name('templates.set-default');
});

// ─── Generate Soal (hanya teacher & individual) ───────────────────────────────
Route::middleware(['auth', 'verified', 'role:teacher,individual'])->group(function () {

    Route::get('/generate-soal', [QuestionSetController::class, 'create'])
        ->name('generate-soal');

    Route::post('/generate-soal', [QuestionSetController::class, 'store'])
        ->middleware(['throttle:generate-soal', 'check.quota'])
        ->name('generate-soal.store');
});

// ─── Admin Panel (super_admin + school_admin) ─────────────────────────────────
Route::middleware(['auth', 'verified', 'role:super_admin,school_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        Route::middleware('role:super_admin')->group(function () {
            Route::get('/schools', [SchoolController::class, 'index'])
                ->name('schools.index');

            Route::get('/schools/create', [SchoolController::class, 'create'])
                ->name('schools.create');

            Route::post('/schools', [SchoolController::class, 'store'])
                ->name('schools.store');

            Route::get('/schools/{school}', [SchoolController::class, 'show'])
                ->name('schools.show');

            Route::patch('/schools/{school}/toggle-active', [SchoolController::class, 'toggleActive'])
                ->name('schools.toggle-active');

            // Subscription management
            Route::get('/schools/{school}/subscription', [SchoolController::class, 'editSubscription'])
                ->name('schools.subscription.edit');

            Route::post('/schools/{school}/subscription', [SchoolController::class, 'updateSubscription'])
                ->name('schools.subscription.update');

            // Reset quota
            Route::post('/schools/{school}/reset-quota', [SchoolController::class, 'resetQuota'])
                ->name('schools.reset-quota');
        });

        // Bank Soal Sekolah — super_admin & school_admin
        Route::get('/bank-soal', [SchoolBankSoalController::class, 'index'])
            ->name('bank-soal.index');

        Route::get('/bank-soal/{questionSet}', [SchoolBankSoalController::class, 'show'])
            ->name('bank-soal.show');

        // Manajemen user individual (super_admin only)
        Route::middleware('role:super_admin')->prefix('individuals')->name('individuals.')->group(function () {
            Route::get('/', [IndividualUserController::class, 'index'])
                ->name('index');
            Route::get('/{user}', [IndividualUserController::class, 'show'])
                ->name('show');
            Route::post('/{user}/update-plan', [IndividualUserController::class, 'updatePlan'])
                ->name('update-plan');
            Route::post('/{user}/reset-quota', [IndividualUserController::class, 'resetQuota'])
                ->name('reset-quota');
            Route::patch('/{user}/toggle-active', [IndividualUserController::class, 'toggleActive'])
                ->name('toggle-active');
            Route::delete('/{user}', [IndividualUserController::class, 'destroy'])
                ->name('destroy');
        });

        // Manajemen guru (super_admin + school_admin)
        Route::get('/teachers', [TeacherController::class, 'index'])
            ->name('teachers.index');

        Route::get('/teachers/create', [TeacherController::class, 'create'])
            ->name('teachers.create');

        Route::post('/teachers', [TeacherController::class, 'store'])
            ->name('teachers.store');

        Route::delete('/teachers/{user}', [TeacherController::class, 'destroy'])
            ->name('teachers.destroy');

        // Kop Surat Sekolah — hanya school_admin
        Route::middleware('role:school_admin')->group(function () {
            Route::get('/letterhead', [SchoolLetterheadController::class, 'edit'])
                ->name('letterhead.edit');

            Route::post('/letterhead', [SchoolLetterheadController::class, 'update'])
                ->name('letterhead.update');

            // Provider AI Sekolah — hanya school_admin yang boleh menentukan,
            // berlaku otomatis untuk semua guru di sekolahnya.
            Route::get('/ai-provider', [SchoolAIProviderController::class, 'edit'])
                ->name('ai-provider.edit');

            Route::post('/ai-provider', [SchoolAIProviderController::class, 'update'])
                ->name('ai-provider.update');
        });
    });

// ─── Profile ──────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
