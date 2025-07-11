<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncedPlayLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogSyncController extends Controller
{
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'logs' => 'required|array',
            'logs.*.event_type' => 'required|string',
            'logs.*.message' => 'required|string',
            'logs.*.logged_at' => 'required|date',
        ]);

        $videotron = $request->user(); // Diambil dari token Sanctum

        $logsToInsert = array_map(function ($log) use ($videotron) {
            return [
                'videotron_id' => $videotron->id,
                'event_type' => $log['event_type'],
                'message' => $log['message'],
                'logged_at' => $log['logged_at'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $validated['logs']);

        DB::table('synced_play_logs')->insert($logsToInsert);

        return response()->json(['message' => 'Logs received.']);
    }
}
