<?php

use App\Http\Controllers\Finance\ChartOfAccountController;
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

    Route::resource('students', \App\Http\Controllers\StudentController::class);
    Route::post('students/{student}/restore', [\App\Http\Controllers\StudentController::class, 'restore'])->name('students.restore');
    Route::delete('students/{student}/force-delete', [\App\Http\Controllers\StudentController::class, 'forceDelete'])->name('students.force-delete');
    Route::get('students/{student}/guardians', [\App\Http\Controllers\StudentController::class, 'guardians'])->name('students.guardians');
    Route::post('students/{student}/guardians/store-and-attach', [\App\Http\Controllers\StudentController::class, 'storeAndAttachGuardian'])->name('students.guardians.store-and-attach');
    Route::post('students/{student}/guardians', [\App\Http\Controllers\StudentController::class, 'attachGuardian'])->name('students.guardians.attach');
    Route::delete('students/{student}/guardians/{guardian}', [\App\Http\Controllers\StudentController::class, 'detachGuardian'])->name('students.guardians.detach');
    Route::put('students/{student}/guardians/{guardian}', [\App\Http\Controllers\StudentController::class, 'updateGuardianRelationship'])->name('students.guardians.update');

    Route::get('guardians/search', [\App\Http\Controllers\GuardianController::class, 'search'])->name('guardians.search');
    Route::resource('guardians', \App\Http\Controllers\GuardianController::class);
    Route::post('guardians/{guardian}/restore', [\App\Http\Controllers\GuardianController::class, 'restore'])->name('guardians.restore');
    Route::delete('guardians/{guardian}/force-delete', [\App\Http\Controllers\GuardianController::class, 'forceDelete'])->name('guardians.force-delete');

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('chart-of-accounts/next-code', [ChartOfAccountController::class, 'getNextCode'])->name('chart-of-accounts.next-code');
        Route::resource('chart-of-accounts', ChartOfAccountController::class);
        Route::post('chart-of-accounts/{chart_of_account}/restore', [ChartOfAccountController::class, 'restore'])->name('chart-of-accounts.restore');
        Route::delete('chart-of-accounts/{chart_of_account}/force-delete', [ChartOfAccountController::class, 'forceDelete'])->name('chart-of-accounts.force-delete');

        Route::resource('transaction-types', \App\Http\Controllers\Finance\TransactionTypeController::class);
        Route::post('transaction-types/{transaction_type}/accounts/{account}', [\App\Http\Controllers\Finance\TransactionTypeController::class, 'updateAccount'])->name('transaction-types.accounts.update');

        Route::resource('journal-entries', \App\Http\Controllers\Finance\JournalEntryController::class);
        Route::post('journal-entries/{journal_entry}/void', [\App\Http\Controllers\Finance\JournalEntryController::class, 'void'])->name('journal-entries.void');

        Route::resource('transactions', \App\Http\Controllers\Finance\TransactionController::class);
        Route::post('transactions/{journal_entry}/void', [\App\Http\Controllers\Finance\TransactionController::class, 'void'])->name('transactions.void');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Finance\ReportController::class, 'index'])->name('index');
            Route::get('general-ledger', [\App\Http\Controllers\Finance\ReportController::class, 'generalLedgerIndex'])->name('general-ledger.index');
            Route::get('general-ledger/show', [\App\Http\Controllers\Finance\ReportController::class, 'generalLedger'])->name('general-ledger.show');
            Route::get('trial-balance', [\App\Http\Controllers\Finance\ReportController::class, 'trialBalanceIndex'])->name('trial-balance.index');
            Route::get('trial-balance/show', [\App\Http\Controllers\Finance\ReportController::class, 'trialBalance'])->name('trial-balance.show');
            Route::get('income-statement', [\App\Http\Controllers\Finance\ReportController::class, 'incomeStatementIndex'])->name('income-statement.index');
            Route::get('income-statement/show', [\App\Http\Controllers\Finance\ReportController::class, 'incomeStatement'])->name('income-statement.show');
            Route::get('balance-sheet', [\App\Http\Controllers\Finance\ReportController::class, 'balanceSheetIndex'])->name('balance-sheet.index');
            Route::get('balance-sheet/show', [\App\Http\Controllers\Finance\ReportController::class, 'balanceSheet'])->name('balance-sheet.show');
        });
    });
});

require __DIR__.'/settings.php';
