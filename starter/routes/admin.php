<?php

use App\Http\Controllers\Admin\DashboardBuilderController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware('lang')->group(function (): void {
    Route::controller(AdminController::class)->group(function (): void {
        Route::get('login', 'login')->name('login');
        Route::post('login', 'send_login')->middleware('throttle:5,1')->name('send_login');
        Route::get('forget-password', 'forget_password')->name('forget_password');
        Route::post('forget-password', 'send_forget_password')->middleware('throttle:5,1')->name('send_forget_password');
        Route::get('reset-password/{token}', 'reset_password')->name('reset_password');
        Route::post('reset-password', 'send_reset_password')->name('send_reset_password');
        Route::post('update-password', 'update_password')->middleware('admin')->name('update_password');
        Route::post('logout', 'logout')->name('logout');
    });
    Route::middleware('admin')->group(function (): void {
        Route::get('home', [HomeController::class, 'index'])->name('home');
        Route::get('change-lang/{lang}', [HomeController::class, 'change_lang'])->name('change_lang');
        Route::prefix('admins')->name('admins.')->controller(AdminController::class)->middleware('can:view_admin')->group(function (): void {
            all_routes('admin');
            Route::post('update-active', 'update_active')->middleware('can:update_admin')->name('update_active');
            Route::post('update-admin-tickets', 'update_admin_tickets')->middleware('can:update_admin')->name('update_admin_tickets');
        });
        Route::prefix('permissions')->name('permissions.')->controller(PermissionController::class)->middleware('can:view_permission')->group(function (): void { all_routes('permission'); });
        Route::get('settings', [SettingController::class, 'index'])->middleware('can:view_setting')->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])->middleware('can:update_setting')->name('settings.update');
        Route::prefix('dashboard-builder')->name('dashboard-builder.')->controller(DashboardBuilderController::class)->group(function (): void {
            Route::get('/', 'index')->name('index'); Route::get('catalog', 'catalog')->name('catalog'); Route::post('preview', 'preview')->name('preview'); Route::post('generate', 'generate')->name('generate');
        });
        foreach (glob(base_path('routes/admin-generated/*.php')) ?: [] as $generated) require $generated;
    });
});
Route::get('privacy', [SettingController::class, 'privacy'])->name('privacy_link');
Route::get('terms', [SettingController::class, 'terms'])->name('terms_link');
Route::get('about', [SettingController::class, 'about'])->name('about_link');
Route::get('refund', [SettingController::class, 'refund'])->name('refund_link');
Route::get('insurance', [SettingController::class, 'insurance'])->name('insurance_link');
