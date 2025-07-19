<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Authentication routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    
    // Collection management
    Route::get('/collection', [CollectionController::class, 'index'])->name('collection.index');
    Route::get('/collection/create', [CollectionController::class, 'create'])->name('collection.create');
    Route::post('/collection', [CollectionController::class, 'store'])->name('collection.store');
    Route::get('/collection/{item}', [CollectionController::class, 'show'])->name('collection.show');
    Route::get('/collection/{item}/edit', [CollectionController::class, 'edit'])->name('collection.edit');
    Route::put('/collection/{item}', [CollectionController::class, 'update'])->name('collection.update');
    Route::delete('/collection/{item}', [CollectionController::class, 'destroy'])->name('collection.destroy');
    
    // Collection export/import
    Route::get('/collection/export/csv', [CollectionController::class, 'exportCsv'])->name('collection.export-csv');
    Route::post('/collection/import/csv', [CollectionController::class, 'importCsv'])->name('collection.import-csv');
    
    // Collection statistics
    Route::get('/collection/statistics', [CollectionController::class, 'statistics'])->name('collection.statistics');
    
    // Bulk operations
    Route::post('/collection/bulk-operation', [CollectionController::class, 'bulkOperation'])->name('collection.bulk-operation');
    
    // Profile management
Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');
    
    // Admin routes
Route::middleware(['can:manage_users'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/admin/groups', [AdminController::class, 'groups'])->name('admin.groups');
    Route::get('/admin/users/{user}', [AdminController::class, 'showUser'])->name('admin.users.show');
    Route::get('/admin/groups/{group}', [AdminController::class, 'showGroup'])->name('admin.groups.show');
});
});

// API routes for AJAX requests
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::post('/collection/scan', [CollectionController::class, 'scanBarcode'])->name('api.collection.scan');
    Route::post('/collection/search', [CollectionController::class, 'search'])->name('api.collection.search');
    Route::post('/collection/share', [CollectionController::class, 'createShareLink'])->name('api.collection.share');
});

// Public shared collection routes
Route::get('/shared/{token}', [CollectionController::class, 'showShared'])->name('collection.shared'); 