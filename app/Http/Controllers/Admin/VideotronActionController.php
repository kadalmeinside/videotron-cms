<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Videotron;
use Illuminate\Http\RedirectResponse;
use Kreait\Firebase\Messaging\CloudMessage;

class VideotronActionController extends Controller
{
    /**
     * Memicu sinkronisasi paksa pada perangkat tertentu melalui FCM.
     */
    public function forceSync(Videotron $videotron): RedirectResponse
    {
        // Anda bisa menggunakan policy otorisasi yang sesuai di sini
        // $this->authorize('manage_videotrons');

        if (empty($videotron->fcm_token)) {
            return back()->with([
                'flash' => [
                    'type' => 'error',
                    'message' => 'Gagal: Perangkat ini tidak memiliki FCM token terdaftar.',
                ]
            ]);
        }

        try {
            $messaging = app('firebase.messaging');
            $message = CloudMessage::withTarget('token', $videotron->fcm_token)
                ->withData(['action' => 'force_sync']);

            $messaging->send($message);

            \Log::info("Perintah force_sync dikirim secara manual ke perangkat: {$videotron->name}");

            return back()->with([
                'flash' => [
                    'type' => 'success',
                    'message' => 'Perintah sinkronisasi berhasil dikirim ke ' . $videotron->name,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error("Gagal mengirim FCM manual ke {$videotron->name}: " . $e->getMessage());
            return back()->with([
                'flash' => [
                    'type' => 'error',
                    'message' => 'Gagal mengirim perintah. Terjadi kesalahan server.',
                ]
            ]);
        }
    }

    public function forceUpdate(Videotron $videotron): RedirectResponse
    {
        $this->authorize('manage_videotrons');

        if (empty($videotron->fcm_token)) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Gagal: Perangkat tidak memiliki FCM token.']);
        }

        // Ambil URL APK terbaru dari file konfigurasi
        // Anda bisa membuat file config/app_settings.php atau menyimpannya di .env
        $latestApkUrl = config('app_settings.latest_apk_url');
        $latestVersionCode = config('app_settings.latest_version_code');

        if (empty($latestApkUrl) || empty($latestVersionCode)) {
             return back()->with('flash', ['type' => 'error', 'message' => 'Gagal: URL APK atau versi terbaru belum diatur di server.']);
        }
        
        // Opsional: Cek apakah perangkat sudah menggunakan versi terbaru
        if ($videotron->app_version_code >= $latestVersionCode) {
            return back()->with('flash', ['type' => 'info', 'message' => 'Info: Perangkat sudah menggunakan versi aplikasi terbaru.']);
        }

        try {
            $messaging = app('firebase.messaging');
            $message = CloudMessage::withTarget('token', $videotron->fcm_token)
                ->withData([
                    'action' => 'force_update',
                    'apk_url' => $latestApkUrl,
                    'version_code' => (string)$latestVersionCode // Kirim sebagai string
                ]);

            $messaging->send($message);

            \Log::info("Perintah force_update dikirim ke: {$videotron->name}");

            return back()->with('flash', ['type' => 'success', 'message' => 'Perintah update berhasil dikirim ke ' . $videotron->name]);

        } catch (\Exception $e) {
            \Log::error("Gagal mengirim FCM update ke {$videotron->name}: " . $e->getMessage());
            return back()->with('flash', ['type' => 'error', 'message' => 'Gagal mengirim perintah. Terjadi kesalahan server.']);
        }
    }
}
