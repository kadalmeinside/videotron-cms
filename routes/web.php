<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;

use App\Http\Controllers\DisplayController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\VideotronController;
use App\Http\Controllers\Admin\VideotronActionController; 
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MusicController;
use App\Http\Controllers\Admin\MediaApprovalController;
use App\Http\Controllers\Admin\PlaylistController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ScheduleBuilderController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\LogController;


Route::get('/', function (Request $request) {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'userIp' => $request->ip(),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION ,
    ]);
});

Route::get('/display', [DisplayController::class, 'index'])->name('display.index');
Route::get('/play/{uuid}', function ($uuid) {
    return Inertia::render('Player/Index', [
        'videotronUuid' => $uuid
    ]);
})->name('player.show');

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])
                ->middleware('guest') // Hanya bisa diakses oleh guest (belum login)
                ->name('login');

    Route::post('login', [AdminAuthenticatedSessionController::class, 'store'])
                ->middleware('guest');

    Route::middleware(['auth', 'verified'])->group(function () {
        
        Route::middleware(['role:admin'])->group(function () {
            Route::resource('roles', RoleController::class);
            Route::resource('permissions', PermissionController::class);
            Route::resource('users', UserController::class);
            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
        });

        Route::middleware(['role:admin|content-manager'])->group(function() {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::resource('videotrons', VideotronController::class);
            Route::resource('clients', ClientController::class);
            Route::prefix('media')->name('media.')->group(function () {
                Route::get('/approvals', [MediaApprovalController::class, 'index'])->name('approvals.index');
                Route::patch('/{medium}/approve', [MediaApprovalController::class, 'approve'])->name('approve');
                Route::delete('/{medium}/reject', [MediaApprovalController::class, 'reject'])->name('reject');
            });
            Route::resource('media', MediaController::class);
            Route::post('/music/analyze', [MusicController::class, 'analyze'])->name('music.analyze');
            Route::resource('music', MusicController::class);
            Route::get('/playlists/{playlist}/music', [PlaylistController::class, 'musicBuilder'])->name('playlists.musicBuilder');
            Route::post('/playlists/{playlist}/music', [PlaylistController::class, 'saveMusicPlaylist'])->name('playlists.saveMusicPlaylist');
            Route::resource('playlists', PlaylistController::class);
            Route::get('/schedule-builder', [ScheduleBuilderController::class, 'index'])->name('schedule.builder');
            Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
            Route::get('/schedules/{schedule:id}', [ScheduleController::class, 'show'])->name('schedules.show');
            Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
            Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
            Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
            Route::get('/logs/export', [LogController::class, 'export'])->name('logs.export');
            Route::post('/videotrons/{videotron}/force-sync', [VideotronActionController::class, 'forceSync'])
                ->name('videotrons.force-sync');
            Route::post('/videotrons/{videotron}/force-update', [VideotronActionController::class, 'forceUpdate'])
                ->name('videotrons.force-update');

        });

    });
});

Route::middleware(['auth', 'verified', 'role:client'])->prefix('siswa')
    ->name('siswa.')->group(function () {
        
});

require __DIR__.'/auth.php';
