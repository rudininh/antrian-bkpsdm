<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? to_route('dashboard')
        : to_route('login');
})->name('home');

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

        Route::get('/loket', [CounterController::class, 'index'])->name('counters.index');
        Route::post('/loket', [CounterController::class, 'store'])->name('counters.store');
        Route::put('/loket/{counter}', [CounterController::class, 'update'])->name('counters.update');
        Route::delete('/loket/{counter}', [CounterController::class, 'destroy'])->name('counters.destroy');
    });

    Route::middleware('can:manage-queues')->group(function () {
        Route::get('/antrian', [QueueController::class, 'index'])->name('queues.index');
        Route::post('/antrian', [QueueController::class, 'store'])->name('queues.store');
        Route::put('/antrian/{queue}', [QueueController::class, 'update'])->name('queues.update');
        Route::delete('/antrian/{queue}', [QueueController::class, 'destroy'])->name('queues.destroy');

        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
        Route::post('/monitoring/{queue}/call', [MonitoringController::class, 'call'])->name('monitoring.call');
        Route::post('/monitoring/{queue}/start', [MonitoringController::class, 'start'])->name('monitoring.start');
        Route::post('/monitoring/{queue}/complete', [MonitoringController::class, 'complete'])->name('monitoring.complete');
        Route::post('/monitoring/{queue}/skip', [MonitoringController::class, 'skip'])->name('monitoring.skip');
    });
});

require __DIR__.'/auth.php';
