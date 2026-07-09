<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Dataset\DatasetController;
use App\Http\Controllers\History\HistoryController;
use App\Http\Controllers\Settings\SettingsController;

Route::middleware(['auth'])->group(function () {
    // Report Generator
    Route::get('/', [ReportController::class, 'index'])->name('report.index');
    Route::post('/reports/generate', [ReportController::class, 'generate'])->name('report.generate');
    Route::post('/reports', [ReportController::class, 'store'])->name('report.store');

    // Students CRUD
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

    // Dataset CRUD
    Route::get('/dataset', [DatasetController::class, 'index'])->name('dataset.index');
    Route::post('/dataset', [DatasetController::class, 'store'])->name('dataset.store');
    Route::delete('/dataset/{dataset}', [DatasetController::class, 'destroy'])->name('dataset.destroy');

    // History list and delete
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::get('/history/student/{student}', [HistoryController::class, 'show'])->name('history.student');
    Route::delete('/history/{report}', [HistoryController::class, 'destroy'])->name('history.destroy');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

require __DIR__.'/auth.php';
