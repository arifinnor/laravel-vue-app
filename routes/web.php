<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::resource('users', UserController::class);
    Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::delete('users/{user}/force-delete', [UserController::class, 'forceDelete'])->name('users.force-delete');

    Route::resource('teachers', \App\Http\Controllers\TeacherController::class);
    Route::post('teachers/{teacher}/restore', [\App\Http\Controllers\TeacherController::class, 'restore'])->name('teachers.restore');
    Route::delete('teachers/{teacher}/force-delete', [\App\Http\Controllers\TeacherController::class, 'forceDelete'])->name('teachers.force-delete');
});

require __DIR__.'/settings.php';
