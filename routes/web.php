<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FeedbackExportController;
use App\Http\Controllers\FeedbackReportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes — Account Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes — Student Feedback
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'feedback.submitter'])->group(function () {
    Route::get('/', [FeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
    Route::get('/feedback/history', [FeedbackController::class, 'history'])->name('feedback.history');
    Route::get('/feedback/thankyou', [FeedbackController::class, 'thankyou'])->name('feedback.thankyou');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes — Admin/Faculty Dashboard
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/feedback/export', FeedbackExportController::class)->name('feedback.export');
    Route::get('/dashboard/feedback/report', FeedbackReportController::class)->name('feedback.report');
});

Route::middleware(['auth', 'active', 'admin.only'])->prefix('dashboard')->group(function () {
    Route::delete('/feedback/{feedback}', [FeedbackController::class, 'destroy'])->name('feedback.destroy');
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::patch('/accounts/{user}/role', [AccountController::class, 'updateRole'])->name('accounts.role');
    Route::patch('/accounts/{user}/status', [AccountController::class, 'toggleStatus'])->name('accounts.status');
    Route::delete('/accounts/{user}', [AccountController::class, 'destroy'])->name('accounts.destroy');
});
