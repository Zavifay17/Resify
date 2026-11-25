<?php

use App\Http\Controllers\AiLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SubstituteController;
use App\Http\Controllers\VisionController;
use Illuminate\Support\Facades\Route;

// Halaman Utama: Arahkan ke Dashboard atau Login
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Autentikasi Pengguna & Demo Quick-Login
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/quick-login', [AuthController::class, 'quickLogin'])->name('quick-login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Google OAuth Routes
Route::get('/auth/google', [AuthController::class, 'googleRedirect'])->name('google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])->name('google.callback');

// Rute Terproteksi (Login Required)
Route::middleware('auth')->group(function () {
    // 1. Dashboard Utama & Metrik
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // 2. Inventaris Dapur Cerdas (Hybrid MySQL + Supabase)
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::post('/', [InventoryController::class, 'store'])->name('store');
        Route::put('/{id}', [InventoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [InventoryController::class, 'destroy'])->name('destroy');
        Route::post('/sync-supabase', [InventoryController::class, 'syncSupabase'])->name('sync-supabase');
        Route::get('/print-report', [InventoryController::class, 'printReport'])->name('print');
    });

    // 3. Vision AI: Deteksi Bahan Makanan lewat Foto (Computer Vision)
    Route::prefix('vision')->name('vision.')->group(function () {
        Route::get('/', [VisionController::class, 'index'])->name('index');
        Route::post('/analyze', [VisionController::class, 'analyze'])->name('analyze');
        Route::post('/save-batch', [VisionController::class, 'saveBatch'])->name('save-batch');
    });

    // 4. Generator Resep AI & Eksplorasi Kuliner Indonesia
    Route::prefix('recipes')->name('recipes.')->group(function () {
        Route::get('/', [RecipeController::class, 'index'])->name('index');
        Route::get('/generator', [RecipeController::class, 'generator'])->name('generator');
        Route::post('/generate', [RecipeController::class, 'generate'])->name('generate');
        Route::post('/rescue-expiring', [RecipeController::class, 'rescueExpiring'])->name('rescue');
        Route::get('/{id}', [RecipeController::class, 'show'])->name('show');
        Route::post('/{id}/favorite', [RecipeController::class, 'toggleFavorite'])->name('favorite');
    });

    // 5. AI Substitute Suggestion (Bahan Pengganti Terdekat & Penyesuaian Rasio)
    Route::post('/substitute/suggest', [SubstituteController::class, 'suggest'])->name('substitute.suggest');

    // 6. Monitoring Token & Log AI (Standar Penilaian RPL)
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [AiLogController::class, 'index'])->name('index');
        Route::get('/{id}', [AiLogController::class, 'show'])->name('show');
    });

    // 7. Pengaturan Sistem, Konfigurasi Gemini API & Supabase Cloud
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/', [SettingController::class, 'update'])->name('update');
        Route::post('/test-gemini', [SettingController::class, 'testGemini'])->name('test-gemini');
        Route::post('/test-supabase', [SettingController::class, 'testSupabase'])->name('test-supabase');
    });
});
