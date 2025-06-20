<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Media;
use App\Models\Playlist;
use App\Models\Schedule;
use App\Models\Videotron;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'clients' => Client::count(),
            'videotrons' => Videotron::where('status', 'active')->count(),
            'playlists' => Playlist::count(),
            'media' => Media::count(),
        ];

        // 2. Data untuk Widget "Sedang Tayang"
        $nowPlaying = Schedule::with(['playlist', 'videotron'])
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->get();

        // 3. Data untuk Widget "Media Menunggu Persetujuan"
        $pendingMedia = Media::with('client')
            ->where('is_approved', false)
            ->latest()
            ->take(5) // Ambil 5 terbaru saja
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'nowPlaying' => $nowPlaying,
            'pendingMedia' => $pendingMedia,
        ]);
    }
}

