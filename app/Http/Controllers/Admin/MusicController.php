<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Music;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use getID3;

class MusicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Music/Index', [
            'musicList' => Music::latest()->paginate(10),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Music/Form', [
            'music' => null,
        ]);
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'audio_file' => 'required|file|mimes:mp3,wav,m4a,aac',
        ]);

        try {
            $getID3 = new getID3;
            $tempFilePath = $request->file('audio_file')->getRealPath();
            $fileInfo = $getID3->analyze($tempFilePath);

            // Ekstrak data yang kita butuhkan
            $metadata = [
                'title' => $fileInfo['tags']['id3v2']['title'][0] ?? '',
                'singer' => $fileInfo['tags']['id3v2']['artist'][0] ?? '',
                'album' => $fileInfo['tags']['id3v2']['album'][0] ?? '',
                'year' => $fileInfo['tags']['id3v2']['year'][0] ?? '',
                'genre' => $fileInfo['tags']['id3v2']['genre'][0] ?? '',
            ];

            return response()->json($metadata);

        } catch (\Exception $e) {
            \Log::error('getID3 gagal menganalisis file: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal membaca metadata file.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'singer' => 'required|string|max:255',
            'album' => 'nullable|string|max:255',
            'year' => 'nullable|string|max:4',
            'genre' => 'nullable|string|max:255',
            'audio_file' => 'required|file|mimes:mp3,wav,m4a,aac',
        ]);

        $path = $request->file('audio_file')->store('music', 'public');
        $duration = 0;

        // --- PERBAIKAN: Gunakan getID3 untuk mendapatkan durasi ---
        try {
            // Inisialisasi getID3
            $getID3 = new getID3;
            // Dapatkan path absolut dari file yang diupload sementara
            $tempFilePath = $request->file('audio_file')->getRealPath();
            // Analisis file
            $fileInfo = $getID3->analyze($tempFilePath);
            
            // Dapatkan durasi dalam detik
            if (!empty($fileInfo['playtime_seconds'])) {
                $duration = (int) round($fileInfo['playtime_seconds']);
            }

        } catch (\Exception $e) {
            // Jika getID3 gagal, kita biarkan durasi tetap 0 dan catat errornya
            \Log::error('getID3 gagal mendapatkan durasi audio: ' . $e->getMessage());
        }

        Music::create([
            'title' => $validated['title'],
            'singer' => $validated['singer'],
            'album' => $validated['album'],
            'year' => $validated['year'],
            'genre' => $validated['genre'],
            'file_path' => $path,
            'duration' => $duration,
        ]);

        return redirect()->route('admin.music.index')->with('success', 'Musik berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Music $music)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Music $music)
    {
        return Inertia::render('Admin/Music/Form', [
            'music' => $music,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Music $music)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'singer' => 'required|string|max:255',
            'album' => 'nullable|string|max:255',
            'year' => 'nullable|string|max:4',
            'genre' => 'nullable|string|max:255',
        ]);

        $music->update($validated);

        return redirect()->route('admin.music.index')->with('success', 'Musik berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Music $music)
    {
        Storage::disk('public')->delete($music->file_path);
        
        $music->delete();

        return redirect()->route('admin.music.index')->with('success', 'Musik berhasil dihapus.');
    }
}
