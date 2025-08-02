<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVideotronRequest;
use App\Http\Requests\Admin\UpdateVideotronRequest;
use App\Models\Schedule;
use App\Models\Videotron;
use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;  
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class VideotronController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Videotron::class);
        $query = Videotron::orderBy('name');

        // Filter pencarian teks
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('location_name', 'LIKE', "%{$search}%")
                  ->orWhere('device_id', 'LIKE', "%{$search}%");
            });
        }

        // --- FILTER BARU UNTUK STATUS ONLINE/OFFLINE ---
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'online') {
                $query->where('last_seen_at', '>=', now()->subMinutes(16));
            } elseif ($status === 'offline') {
                $query->where(function ($q) {
                    $q->where('last_seen_at', '<', now()->subMinutes(16))
                      ->orWhereNull('last_seen_at');
                });
            }
        }

        $videotrons = $query->paginate(10)->withQueryString();

        // Tambahkan accessor ke data yang dikirim ke Vue
        $videotrons->getCollection()->transform(function ($videotron) {
            $videotron->is_online = $videotron->is_online;
            $videotron->last_seen_at_for_humans = $videotron->last_seen_at_for_humans;
            return $videotron;
        });

        return Inertia::render('Admin/Videotrons/Index', [
            'videotronList' => $videotrons,
            'allPlaylists' => Playlist::orderBy('name')->get(['id', 'name']),
            'allSchedules' => Schedule::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status']), // <-- Tambahkan status ke filter
            'can' => ['manage_videotrons' => $request->user()->can('manage_videotrons')]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVideotronRequest $request)
    {
        $validated = $request->validated();
        
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        Videotron::create($validated);
        return redirect()->route('admin.videotrons.index')->with('success', 'Videotron berhasil dibuat.');
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVideotronRequest $request, Videotron $videotron)
    {
        $validated = $request->validated();
        
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $videotron->update($validated);
        return redirect()->route('admin.videotrons.index')->with('success', 'Videotron berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Videotron $videotron)
    {
        $this->authorize('delete', $videotron);
        $videotron->delete();
        return Redirect::route('admin.videotrons.index')->with([
            'message' => 'Videotron berhasil dihapus.',
            'type' => 'success'
        ]);
    }
}
