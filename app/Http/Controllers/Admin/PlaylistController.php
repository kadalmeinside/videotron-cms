<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Playlist;
use App\Models\Music;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
// Gunakan Form Request yang lebih sederhana
use App\Http\Requests\Admin\StorePlaylistRequest; 
use App\Http\Requests\Admin\UpdatePlaylistRequest;

class PlaylistController extends Controller
{
    /**
     * Menampilkan daftar semua playlist.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Playlist::class);
        
        $query = Playlist::withCount('musics')->latest(); // Hitung jumlah musik

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', "%{$request->input('search')}%");
        }
        
        $playlists = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Playlists/Index', [
            'playlists' => $playlists,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Menampilkan form untuk membuat playlist baru.
     */
    public function create()
    {
        $this->authorize('create', Playlist::class);
        return Inertia::render('Admin/Playlists/Form', [
            // Kirim semua musik yang tersedia ke form
            'allMusic' => Music::latest()->get(['id', 'title', 'singer']),
            'playlist' => null,
        ]);
    }

    /**
     * Menyimpan playlist baru.
     */
    public function store(StorePlaylistRequest $request)
    {
        $validated = $request->validated();
        
        DB::transaction(function () use ($validated, $request) {
            $playlist = Playlist::create($request->only('name', 'description'));
            
            $musicToSync = [];
            if (!empty($validated['music'])) {
                foreach ($validated['music'] as $index => $musicItem) {
                    $musicToSync[$musicItem['id']] = ['play_order' => $index + 1];
                }
            }
            
            $playlist->musics()->sync($musicToSync);
        });

        return Redirect::route('admin.playlists.index')->with('success', 'Playlist musik berhasil dibuat.');
    }

    /**
     * Menampilkan form untuk mengedit playlist.
     */
    public function edit(Playlist $playlist)
    {
        $this->authorize('update', $playlist);
        
        // Load musik yang sudah terhubung, urutkan berdasarkan play_order
        $playlist->load(['musics' => function ($query) {
            $query->orderBy('music_playlist.play_order');
        }]);

        return Inertia::render('Admin/Playlists/Form', [
            'allMusic' => Music::latest()->get(['id', 'title', 'singer']),
            'playlist' => $playlist,
        ]);
    }


    /**
     * Memperbarui playlist yang sudah ada.
     */
    public function update(UpdatePlaylistRequest $request, Playlist $playlist)
    {
        $validated = $request->validated();
        
        DB::transaction(function () use ($validated, $playlist, $request) {
            $playlist->update($request->only('name', 'description'));
            
            $musicToSync = [];
            if (!empty($validated['music'])) {
                foreach ($validated['music'] as $index => $musicItem) {
                    $musicToSync[$musicItem['id']] = ['play_order' => $index + 1];
                }
            }
            
            $playlist->musics()->sync($musicToSync);
        });

        return Redirect::route('admin.playlists.index')->with('success', 'Playlist musik berhasil diperbarui.');
    }


    /**
     * Menghapus playlist.
     */
    public function destroy(Playlist $playlist)
    {
        $this->authorize('delete', $playlist);
        $playlist->delete();
        return Redirect::route('admin.playlists.index')->with('success', 'Playlist berhasil dihapus.');
    }
}
