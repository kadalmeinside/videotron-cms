<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeviceApiController extends Controller
{
    /**
     * Menerima sinyal "heartbeat" dari perangkat dan memperbarui waktu terakhir terlihat.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $videotron = $request->user();

        // Perbarui timestamp terakhir terlihat
        $videotron->last_seen_at = now();

        // Jika request menyertakan app_version_code, perbarui juga
        if ($request->has('app_version_code')) {
            $videotron->app_version_code = $request->input('app_version_code');
        }

        $videotron->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Heartbeat received.'
        ]);
    }

    /**
     * Menerima dan menyimpan token FCM dari perangkat.
     */
    public function registerFCMToken(Request $request): JsonResponse
    {
        // Validasi input untuk memastikan fcm_token ada dan berupa string
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        // Mendapatkan model user (perangkat) yang sedang terotentikasi
        $videotron = $request->user();
        
        // Simpan atau perbarui token FCM di database
        $videotron->fcm_token = $request->input('fcm_token');
        $videotron->save();

        return response()->json([
            'status' => 'success',
            'message' => 'FCM token has been registered successfully.'
        ]);
    }
}
