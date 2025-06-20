<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\PlayLogController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/player/{videotron:id}/now', [PlayerController::class, 'now']);
Route::post('/log/play', [PlayLogController::class, 'store']);
Route::post('/player/login', [PlayerController::class, 'login']);

// Kita juga butuh endpoint otorisasi broadcast standar dari Sanctum
// Route ini akan dipanggil oleh Laravel Echo setelah mendapatkan token
Route::middleware('auth:sanctum')->post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
});