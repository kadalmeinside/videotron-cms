<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScheduleRequest;
use App\Models\Playlist;
use App\Models\Schedule;
use App\Models\Videotron;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Schedule::class);
        
        // Cek jika request datang dari FullCalendar (AJAX)
        if ($request->wantsJson()) {
            $schedules = Schedule::with(['playlist', 'videotron'])
                ->where('start_time', '>=', $request->start)
                ->where('end_time', '<=', $request->end)
                ->get();
            
            // Format data sesuai kebutuhan FullCalendar
            return response()->json($schedules->map(fn($s) => [
                'id' => $s->id,
                'title' => "{$s->playlist->name} @ {$s->videotron->name}",
                'start' => $s->start_time,
                'end' => $s->end_time,
            ]));
        }
        
        // Render halaman Inertia
        return Inertia::render('Admin/Schedules/Index', [
            'playlists' => Playlist::get(['id', 'name']),
            'videotrons' => Videotron::get(['id', 'name']),
            'can' => ['manage_schedules' => $request->user()->can('manage_schedules')]
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
    public function store(StoreScheduleRequest $request)
    {
        $schedule = Schedule::create($request->validated());
        return Redirect::back()->with(['message' => 'Jadwal berhasil dibuat.', 'type' => 'success']);
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
