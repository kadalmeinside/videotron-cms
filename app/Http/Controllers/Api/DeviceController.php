<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Videotron;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        // $request->user() akan berisi model Videotron yang sudah terotentikasi via Sanctum
        $videotron = $request->user();

        // Ambil semua item jadwal untuk videotron ini dari hari ini dan seterusnya
        $scheduleItems = $videotron->scheduleItems()
            ->with('media') // Eager load media terkait
            ->where('play_at', '>=', now()->startOfDay())
            ->orderBy('play_at', 'asc')
            ->get();

        // Kelompokkan jadwal berdasarkan tanggal
        $groupedByDate = $scheduleItems->groupBy(function ($item) {
            return $item->play_at->format('Y-m-d');
        });

        // Dapatkan daftar media unik yang dibutuhkan beserta URL download-nya
        $requiredMedia = $scheduleItems->pluck('media')->unique('id')->map(function ($media) {
            if ($media->source_type === 'local') {
                return [
                    'id' => $media->id,
                    'file_name' => basename($media->source_value),
                    'download_url' => Storage::url($media->source_value),
                ];
            }
            return null;
        })->filter()->values(); // Hapus item null dari media non-lokal

        return response()->json([
            'videotron_name' => $videotron->name,
            'schedules' => $groupedByDate,
            'required_media' => $requiredMedia,
        ]);
    }
}