<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\PlayLogController;
use App\Http\Controllers\Admin\ScheduleBuilderController;
use App\Http\Controllers\Admin\Api\ScheduleItemController;
use App\Http\Controllers\Api\DeviceController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/player/{videotron:uuid}/schedule', [PlayerController::class, 'getSchedule'])->middleware('auth:sanctum');

Route::get('/player/{videotron:id}/now', [PlayerController::class, 'now']);
Route::post('/log/play', [PlayLogController::class, 'store']);
Route::post('/player/login', [PlayerController::class, 'login']);

Route::middleware('auth:sanctum')->post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
});

Route::middleware(['auth:sanctum'])->prefix('admin/schedule')->name('api.admin.schedule.')->group(function () {
    Route::get('/events', [ScheduleBuilderController::class, 'getEvents'])->name('events');
    Route::post('/save', [ScheduleBuilderController::class, 'saveSchedule'])->name('save');
});

Route::middleware(['auth:sanctum'])->prefix('admin')->name('api.admin.')->group(function () {
    Route::apiResource('schedule-items', ScheduleItemController::class)->except(['index', 'show']);
});

Route::get('/device/check/{device_id}', [DeviceController::class, 'checkStatus']);

// Endpoint untuk perangkat melakukan login dan mendapatkan token
Route::post('/device/login', [DeviceController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Endpoint untuk perangkat yang sudah login mengunduh semua jadwalnya
    Route::get('/device/schedules', [DeviceController::class, 'getSchedules']);
});