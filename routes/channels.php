<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Videotron;
use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// --- VERSI FINAL YANG AMAN & BENAR ---
Broadcast::channel('monitoring', function ($user) {
    // Fungsi otorisasi ini akan menerima user yang sudah terotentikasi,
    // baik itu model 'User' (untuk admin) atau model 'Videotron' (untuk player).

    // Cek apakah user yang terotentikasi adalah instance dari App\Models\User
    if ($user instanceof \App\Models\User) {
        // Jika admin/user, cek permission-nya untuk bisa "mendengarkan"
        if ($user->can('view_reports')) {
            // Kembalikan datanya untuk ditampilkan di daftar 'hadir'
            return ['id' => 'admin-'.$user->id, 'name' => $user->name . ' (Admin)'];
        }
    }

    // Cek apakah user yang terotentikasi adalah instance dari App\Models\Videotron
    if ($user instanceof \App\Models\Videotron) {
        Log::info('[BROADCAST AUTH] Otorisasi BERHASIL untuk Videotron Player.');
        // Jika player videotron, kembalikan datanya
        return [
            'id' => $user->id, // Gunakan UUID sebagai ID unik di frontend
            'name' => $user->name,
        ];
    }
    
    // Jika tidak teridentifikasi sebagai User atau Videotron, tolak koneksi.
    return false;

}, ['guards' => ['web', 'sanctum']]);
