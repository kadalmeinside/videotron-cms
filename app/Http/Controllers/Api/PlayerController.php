<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\VideotronOnline;
use App\Models\Videotron;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PlayerController extends Controller
{
    public function getSchedule(Request $request, Videotron $videotron)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $scheduleItems = ScheduleItem::with('media')
            ->where('videotron_id', $videotron->id)
            ->whereDate('play_at', $request->date)
            ->orderBy('play_at', 'asc')
            ->get();

        $formattedSchedule = $scheduleItems->map(function ($item) {
            return [
                'play_at' => $item->play_at->toIso8601String(),
                'media' => [
                    'id' => $item->media->id,
                    'source_type' => $item->media->source_type,
                    'source_value' => $item->media->source_type === 'local' 
                        ? Storage::url($item->media->source_value) 
                        : $item->media->source_value,
                    'duration' => $item->media->duration,

                    'top_banner_url' => $item->media->top_banner_path ? Storage::url($item->media->top_banner_path) : null,
                    'bottom_banner_url' => $item->media->bottom_banner_path ? Storage::url($item->media->bottom_banner_path) : null,
                    'running_text' => $item->media->running_text,
                ]
            ];
        });

        return response()->json([
            'date' => $request->date,
            'schedule' => $formattedSchedule,
        ]);
    }

    public function now(Videotron $videotron)
    {
        $schedule = Schedule::with(['playlist.media' => function ($query) {
                $query->orderBy('media_playlist.play_order');
            }])
            ->where('videotron_id', $videotron->id)
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->first();

        $serverTime = now()->format('Y-m-d H:i:s T');

        if (!$schedule || !$schedule->playlist || $schedule->playlist->media->isEmpty()) {
            return response()->json([
                'status' => 'idle',
                'playlist' => null,
                'server_time' => $serverTime,
            ]);
        }
        
        $formattedMedia = $schedule->playlist->media->map(function ($media) {
            return [
                'id' => $media->id,
                'title' => $media->title,
                'source_type' => $media->source_type,
                'source_value' => $media->source_type === 'local' && $media->source_value ? Storage::url($media->source_value) : $media->source_value,
                'duration' => $media->duration,
                'top_banner_url' => $media->top_banner_path ? Storage::url($media->top_banner_path) : null,
                'bottom_banner_url' => $media->bottom_banner_path ? Storage::url($media->bottom_banner_path) : null,
                'running_text' => $media->running_text,
            ];
        });

        return response()->json([
            'status' => 'playing',
            'schedule_id' => $schedule->id,
            'videotron_id' => $videotron->id,
            'playlist' => [
                'id' => $schedule->playlist->id,
                'name' => $schedule->playlist->name,
                'media' => $formattedMedia,
            ],
            'server_time' => $serverTime,
        ]);
    }

    public function issueToken(Request $request)
    {
        $request->validate(['uuid' => 'required|uuid|exists:videotrons,uuid']);

        $videotron = Videotron::where('uuid', $request->uuid)->firstOrFail();

        // Hapus token lama jika ada untuk menjaga kebersihan
        $videotron->tokens()->delete();

        // Buat token baru yang hanya bisa digunakan untuk 'monitoring'
        $token = $videotron->createToken('videotron-token', ['monitoring:join'])->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function ping(Videotron $videotron)
    {
        // Memicu event VideotronOnline, mengirim data videotron ke semua pendengar
        broadcast(new VideotronOnline($videotron));

        // Kirim respons sukses kembali ke player
        return response()->json(['status' => 'ping_received']);
    }

    public function authorizePlayer(Request $request)
    {
        // Logika ini secara manual mengotorisasi koneksi untuk channel yang diminta.
        // Karena Player adalah "tamu" (tidak login), Laravel akan menggunakan
        // otorisasi channel yang tidak memerlukan $user (seperti yang ada di routes/channels.php).
        return Broadcast::auth($request);
    }

    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'uuid' => ['required', 'uuid'],
    //         'password' => ['required', 'string'],
    //     ]);
        
    //     $videotron = Videotron::where('id', $request->uuid)->first();

    //     // Verifikasi videotron dan password
    //     if (! $videotron || ! Hash::check($request->password, $videotron->password)) {
    //         throw ValidationException::withMessages([
    //             'uuid' => ['Kredensial yang diberikan tidak cocok dengan catatan kami.'],
    //         ]);
    //     }

    //     // Hapus token lama jika ada untuk menjaga kebersihan
    //     $videotron->tokens()->delete();

    //     // Buat token baru
    //     $token = $videotron->createToken('videotron-auth-token')->plainTextToken;

    //     return response()->json(['token' => $token]);
    // }

    public function login(Request $request)
    {
        $request->validate([
            'id' => ['nullable', 'string', 'exists:videotrons,id'],
            'device_id' => ['nullable', 'string', 'exists:videotrons,device_id'],
            'password' => ['required', 'string'],
        ]);

        $videotron = Videotron::where('id', $request->id)
                                ->orWhere('device_id', $request->device_id)
                                ->first();

        if (! $videotron || ! Hash::check($request->password, $videotron->password)) {
            throw ValidationException::withMessages([
                'device_id' => ['Kredensial yang diberikan tidak cocok.'],
            ]);
        }

        $videotron->tokens()->delete();
        $token = $videotron->createToken('stb-auth-token')->plainTextToken;

        return response()->json(['token' => $token]);
    }
}
