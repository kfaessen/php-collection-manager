<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TOTPController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\NotificationController;

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-totp', [AuthController::class, 'verifyTOTP'])->name('verify.totp');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

// OAuth routes
Route::get('/oauth/{provider}', [OAuthController::class, 'redirect'])->name('oauth.redirect');
Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback'])->name('oauth.callback');

// TOTP routes
Route::middleware('auth')->group(function () {
    Route::get('/totp/setup', [TOTPController::class, 'showSetup'])->name('totp.setup');
    Route::post('/totp/enable', [TOTPController::class, 'enable'])->name('totp.enable');
    Route::post('/totp/disable', [TOTPController::class, 'disable'])->name('totp.disable');
    Route::get('/totp/backup-codes', [TOTPController::class, 'showBackupCodes'])->name('totp.backup-codes');
    Route::post('/totp/regenerate-backup-codes', [TOTPController::class, 'regenerateBackupCodes'])->name('totp.regenerate-backup-codes');
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');
    Route::put('/profile/disable-totp', [ProfileController::class, 'disableTOTP'])->name('profile.disable-totp');
});

// Notification routes
Route::middleware('auth')->group(function () {
    Route::get('/notifications/vapid-key', [NotificationController::class, 'getVapidKey'])->name('notifications.vapid-key');
    Route::post('/notifications/subscribe', [NotificationController::class, 'subscribe'])->name('notifications.subscribe');
    Route::post('/notifications/unsubscribe', [NotificationController::class, 'unsubscribe'])->name('notifications.unsubscribe');
    Route::post('/notifications/test', [NotificationController::class, 'test'])->name('notifications.test');
    Route::get('/notifications/settings', [NotificationController::class, 'settings'])->name('notifications.settings');
    Route::put('/notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.preferences');
});

Route::get('/', function () {
    return view('welcome');
});

// Admin routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    
    // Users management
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    
    // Roles management
    Route::get('/roles', [AdminController::class, 'roles'])->name('roles.index');
    Route::get('/roles/create', [AdminController::class, 'createRole'])->name('roles.create');
    Route::post('/roles', [AdminController::class, 'storeRole'])->name('roles.store');
    Route::get('/roles/{role}/edit', [AdminController::class, 'editRole'])->name('roles.edit');
    Route::put('/roles/{role}', [AdminController::class, 'updateRole'])->name('roles.update');
    Route::delete('/roles/{role}', [AdminController::class, 'destroyRole'])->name('roles.destroy');
    
    // Permissions management
    Route::get('/permissions', [AdminController::class, 'permissions'])->name('permissions.index');
    Route::get('/permissions/create', [AdminController::class, 'createPermission'])->name('permissions.create');
    Route::post('/permissions', [AdminController::class, 'storePermission'])->name('permissions.store');
    Route::get('/permissions/{permission}/edit', [AdminController::class, 'editPermission'])->name('permissions.edit');
    Route::put('/permissions/{permission}', [AdminController::class, 'updatePermission'])->name('permissions.update');
    Route::delete('/permissions/{permission}', [AdminController::class, 'destroyPermission'])->name('permissions.destroy');
});
