<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Playlist;
use App\Models\Schedule;
use App\Models\Videotron;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeviceController extends Controller
{
    /**
     * Mengecek status sebuah perangkat berdasarkan Device ID-nya.
     * Dipanggil saat aplikasi pertama kali dibuka.
     */
    public function checkStatus(string $device_id)
    {
        $videotron = Videotron::where('device_id', $device_id)->first();

        // Kasus 1: Perangkat tidak ditemukan sama sekali
        if (!$videotron) {
            return response()->json([
                'status' => 'unregistered',
                'message' => 'Perangkat ini belum terdaftar di sistem.'
            ], 404);
        }

        // Kasus 2: Perangkat ditemukan, cek kolom statusnya
        switch ($videotron->status) {
            case 'active':
                return response()->json([
                    'status' => 'active',
                    'message' => 'Perangkat terdaftar dan aktif, silahkan login.'
                ]);
            case 'inactive':
                return response()->json([
                    'status' => 'inactive',
                    'message' => 'Perangkat ini dinonaktifkan oleh admin.'
                ], 403);
            case 'maintenance':
                return response()->json([
                    'status' => 'maintenance',
                    'message' => 'Perangkat ini sedang dalam perbaikan.'
                ], 403);
            default:
                // Untuk status lain yang mungkin ada atau belum di-set
                return response()->json([
                    'status' => 'pending_approval',
                    'message' => 'Status perangkat tidak diketahui atau menunggu persetujuan.'
                ], 403);
        }
    }

    /**
     * Mengotentikasi perangkat dan membuat API token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'device_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $videotron = Videotron::where('device_id', $request->device_id)->first();

        // Verifikasi apakah videotron ditemukan, aktif, dan password-nya cocok
        if (!$videotron || $videotron->status !== 'active' || !Hash::check($request->password, $videotron->password)) {
            throw ValidationException::withMessages([
                'device_id' => ['Kredensial tidak valid atau perangkat tidak aktif.'],
            ]);
        }

        $videotron->tokens()->delete();
        $token = $videotron->createToken('device-token')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    /**
     * Mengambil semua jadwal yang akan datang untuk perangkat yang terotentikasi.
     */
    public function getSchedules(Request $request)
    {
        $videotron = $request->user();
        $videotron->load(['scheduleItems.media', 'backgroundPlaylist.musics']);

        $scheduleItems = $videotron->scheduleItems()
            ->where('play_at', '>=', now()->startOfDay())
            ->get();
        
        $groupedVideoSchedules = $scheduleItems->groupBy(function ($item) {
            return $item->play_at->format('Y-m-d');
        });

        $requiredFiles = collect();

        // Mengumpulkan file video dari jadwal
        foreach ($scheduleItems as $item) {
            if ($item->media && $item->media->source_type === 'local') {
                // --- 2. PERBAIKAN NAMA FILE VIDEO ---
                $extension = pathinfo($item->media->source_value, PATHINFO_EXTENSION);
                $safeFileName = Str::slug($item->media->title) . '.' . $extension;

                $requiredFiles->push([
                    'id' => 'video_' . $item->media->id,
                    'type' => 'video',
                    'file_name' => $safeFileName,
                    'download_url' => Storage::url($item->media->source_value),
                ]);
            }
        }

        // Mengumpulkan file musik dari playlist latar
        if ($videotron->backgroundPlaylist) {
            foreach ($videotron->backgroundPlaylist->musics as $music) {
                // --- 3. PERBAIKAN NAMA FILE MUSIK ---
                $extension = pathinfo($music->file_path, PATHINFO_EXTENSION);
                $safeFileName = Str::slug($music->singer . '-' . $music->title) . '.' . $extension;

                $requiredFiles->push([
                    'id' => 'music_' . $music->id,
                    'type' => 'music',
                    'file_name' => $safeFileName,
                    'download_url' => Storage::url($music->file_path),
                ]);
            }
        }

        return response()->json([
            'videotron_name' => $videotron->name,
            'video_schedules' => $groupedVideoSchedules,
            'background_playlist' => $videotron->backgroundPlaylist,
            'required_files' => $requiredFiles->unique('download_url')->values()->all(),
        ]);
    }

    public function getConfig(Request $request)
    {
        $videotron = $request->user();

        $schedule = $videotron->schedule;
        $playlist = $videotron->backgroundPlaylist;

        return response()->json([
            'videotron_name' => $videotron->name,
            'assigned_schedule' => $schedule ? [
                'id' => $schedule->id,
                'name' => $schedule->name,
                'version' => $schedule->updated_at->timestamp,
            ] : null,
            'assigned_playlist' => $playlist ? [
                'id' => $playlist->id,
                'name' => $playlist->name,
                'version' => $playlist->updated_at->timestamp,
            ] : null,
            'current_server_time' => now()->timestamp,
        ]);
    }

    /**
     * Mengambil detail lengkap dari sebuah template jadwal video.
     */
    public function getScheduleDetail(Request $request, Schedule $schedule)
    {
        // if ($request->user()->schedule_id !== $schedule->id) {
        //     abort(403, 'Akses ditolak.');
        // }

        $schedule->load('scheduleItems.media');
        
        $requiredFiles = $schedule->scheduleItems->pluck('media')->filter(function ($media) {
            return $media->source_type === 'local';
        })->unique('id')->map(function ($media) {
            $extension = pathinfo($media->source_value, PATHINFO_EXTENSION);
            $safeFileName = Str::slug($media->title) . '.' . $extension;
            return [
                'id' => 'video_' . $media->id,
                'type' => 'video',
                'file_name' => $safeFileName,
                'download_url' => Storage::url($media->source_value),
            ];
        })->values();

        return response()->json([
            'id' => $schedule->id,
            'name' => $schedule->name,
            'version' => $schedule->updated_at->timestamp,
            'items' => $schedule->scheduleItems,
            'required_files' => $requiredFiles,
        ]);
    }
    
    /**
     * Mengambil detail lengkap dari sebuah playlist musik.
     */
    public function getPlaylistDetail(Request $request, Playlist $playlist)
    {
        // if ($request->user()->playlist_id !== $playlist->id) {
        //     abort(403, 'Akses ditolak.');
        // }

        $playlist->load('musics');
        
        $requiredFiles = $playlist->musics->unique('id')->map(function ($music) {
            $extension = pathinfo($music->file_path, PATHINFO_EXTENSION);
            $safeFileName = Str::slug($music->singer . '-' . $music->title) . '.' . $extension;
            return [
                'id' => 'music_' . $music->id,
                'type' => 'music',
                'file_name' => $safeFileName,
                'download_url' => Storage::url($music->file_path),
            ];
        })->values();

        return response()->json([
            'id' => $playlist->id,
            'name' => $playlist->name,
            'version' => $playlist->updated_at->timestamp,
            'tracks' => $playlist->musics,
            'required_files' => $requiredFiles,
        ]);
    }
}