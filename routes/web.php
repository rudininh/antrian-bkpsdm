<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicGuestBookController;
use App\Http\Controllers\PublicQueueController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SystemUpdateController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicQueueController::class, 'index'])->name('home');
Route::get('/ambil-antrian', [PublicQueueController::class, 'index'])->name('public.queue.index');
Route::post('/ambil-antrian', [PublicQueueController::class, 'store'])->name('public.queue.store');
Route::get('/ambil-antrian/sukses/{queue}', [PublicQueueController::class, 'success'])->name('public.queue.success');
Route::get('/buku-tamu', [PublicGuestBookController::class, 'kiosk'])->name('public.guest-book.kiosk');
Route::post('/buku-tamu', [PublicGuestBookController::class, 'upsertFromKiosk'])->name('public.guest-book.kiosk.store');
Route::get('/ambil-antrian/buku-tamu/{queue}', [PublicGuestBookController::class, 'show'])->name('public.guest-book.show');
Route::put('/ambil-antrian/buku-tamu/{queue}', [PublicGuestBookController::class, 'upsert'])->name('public.guest-book.upsert');
Route::get('/monitor-publik', [PublicQueueController::class, 'monitor'])->name('public.monitor');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('can:manage-master-data')->group(function () {
        Route::get('/layanan', [ServiceController::class, 'index'])->name('services.index');
        Route::post('/layanan', [ServiceController::class, 'store'])->name('services.store');
        Route::put('/layanan/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('/layanan/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
    });

    Route::middleware('can:manage-system')->group(function () {
        Route::get('/pengaturan/update-server', [SystemUpdateController::class, 'index'])->name('system.update.index');
        Route::post('/pengaturan/update-server/run', [SystemUpdateController::class, 'runUpdate'])->name('system.update.run');
        Route::post('/pengaturan/update-server/artisan/{action}', [SystemUpdateController::class, 'runArtisanAction'])
            ->whereIn('action', ['down', 'up', 'optimize-clear'])
            ->name('system.update.artisan');
    });

    Route::middleware('can:manage-queues')->group(function () {
        Route::get('/antrian', [QueueController::class, 'index'])->name('queues.index');
        Route::post('/antrian', [QueueController::class, 'store'])->name('queues.store');
        Route::put('/antrian/{queue}', [QueueController::class, 'update'])->name('queues.update');
        Route::delete('/antrian/{queue}', [QueueController::class, 'destroy'])->name('queues.destroy');

        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
        Route::post('/monitoring/{queue}/call', [MonitoringController::class, 'call'])->name('monitoring.call');
        Route::post('/monitoring/{queue}/recall', [MonitoringController::class, 'recall'])->name('monitoring.recall');
        Route::post('/monitoring/{queue}/start', [MonitoringController::class, 'start'])->name('monitoring.start');
        Route::post('/monitoring/{queue}/complete', [MonitoringController::class, 'complete'])->name('monitoring.complete');
        Route::post('/monitoring/{queue}/skip', [MonitoringController::class, 'skip'])->name('monitoring.skip');
    });
});

require __DIR__.'/auth.php';
