<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlaylistRequest;
use App\Http\Requests\Admin\UpdatePlaylistRequest;
use App\Models\Media;
use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PlaylistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) // <-- Tambahkan Request $request
    {
        $this->authorize('viewAny', Playlist::class);
        
        // Mulai query
        $query = Playlist::withCount('media')->latest();

        // Tambahkan logika pencarian
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'LIKE', "%{$search}%");
        }
        
        // Eksekusi query dengan paginasi
        $playlists = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Playlists/Index', [
            'playlists' => $playlists,
            'filters' => $request->only(['search']), // <-- Kirim filter ke frontend
            'can' => ['manage_playlists' => auth()->user()->can('manage_playlists')]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Playlist::class);
        return Inertia::render('Admin/Playlists/Form', [
            'allMedia' => Media::where('is_approved', true)->get(['id', 'title', 'source_type', 'source_value']),
            'playlist' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlaylistRequest $request)
    {
        $validated = $request->validated();
        
        DB::transaction(function () use ($validated) {
            // PERBAIKAN: Hanya buat playlist dengan data yang relevan
            $playlist = Playlist::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
            ]);
            
            // Format data untuk sync()
            $mediaToSync = [];
            if (!empty($validated['media'])) {
                foreach ($validated['media'] as $mediaItem) {
                    $mediaToSync[$mediaItem['id']] = ['play_order' => $mediaItem['play_order']];
                }
            }
            
            $playlist->media()->sync($mediaToSync);
        });

        return Redirect::route('admin.playlists.index')->with(['message' => 'Playlist berhasil dibuat.', 'type' => 'success']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Playlist $playlist)
    {
        $this->authorize('update', $playlist);
        
        // Load media yang sudah terhubung, dan urutkan berdasarkan 'play_order'
        $playlist->load(['media' => function ($query) {
            $query->orderBy('media_playlist.play_order');
        }]);

        return Inertia::render('Admin/Playlists/Form', [
            'allMedia' => Media::where('is_approved', true)->get(['id', 'title', 'source_type', 'source_value']),
            'playlist' => $playlist,
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlaylistRequest $request, Playlist $playlist)
    {
        $validated = $request->validated();
        
        DB::transaction(function () use ($validated, $playlist) {
            // PERBAIKAN: Hanya update playlist dengan data yang relevan
            $playlist->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
            ]);
            
            $mediaToSync = [];
            if (!empty($validated['media'])) {
                foreach ($validated['media'] as $mediaItem) {
                    $mediaToSync[$mediaItem['id']] = ['play_order' => $mediaItem['play_order']];
                }
            }
            
            $playlist->media()->sync($mediaToSync);
        });

        return Redirect::route('admin.playlists.index')->with(['message' => 'Playlist berhasil diperbarui.', 'type' => 'success']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Playlist $playlist)
    {
        $this->authorize('delete', $playlist);
        $playlist->delete(); // Relasi di tabel pivot akan terhapus otomatis jika foreign key di set `onDelete('cascade')`
        return Redirect::route('admin.playlists.index')->with(['message' => 'Playlist berhasil dihapus.', 'type' => 'success']);
    }
}
