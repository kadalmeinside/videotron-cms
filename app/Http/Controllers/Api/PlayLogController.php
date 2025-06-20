<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\PlayLog; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlayLogController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'media_id' => 'required|exists:media,id',
            'videotron_id' => 'required|exists:videotrons,id',
            'playlist_id' => 'required|exists:playlists,id',
            'schedule_id' => 'required|exists:schedules,id',
        ]);

        PlayLog::create($validated);

        return response()->noContent(); 
    }
}
