<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScheduleRequest;
use App\Models\Playlist;
use App\Models\Schedule;
use App\Models\Media;
use App\Models\ScheduleItem;
use App\Models\Videotron;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('manage_schedules');

        $videotrons = Videotron::orderBy('name')
            ->with(['scheduleItems' => function ($query) {
                // Ambil 3 jadwal berikutnya yang akan datang
                $query->where('play_at', '>=', now())
                      ->orderBy('play_at', 'asc')
                      ->limit(3)
                      ->with('media:id,title'); // Ambil juga judul medianya
            }])
            ->get();

        return Inertia::render('Admin/Schedules/Index', [
            'videotrons' => $videotrons,
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
    // public function store(StoreScheduleRequest $request)
    // {
    //     $schedule = Schedule::create($request->validated());
    //     return Redirect::back()->with(['message' => 'Jadwal berhasil dibuat.', 'type' => 'success']);
    // }

    /**
     * Display the specified resource.
     */
    public function show(Videotron $videotron)
    {
        $this->authorize('manage_schedules');

        // Ambil semua item jadwal untuk videotron ini, dan eager load media terkait
        $items = $videotron->scheduleItems()->with('media:id,title,duration')->orderBy('play_at')->get();

        // Kelompokkan item jadwal berdasarkan tanggal (YYYY-MM-DD)
        $scheduledDays = $items->groupBy(function ($item) {
            return $item->play_at->format('Y-m-d');
        });

        return Inertia::render('Admin/Schedules/Builder', [
            // Kirim data videotron yang sedang diedit
            'videotron' => $videotron,
            // Kirim jadwal yang sudah dikelompokkan
            'scheduledDays' => $scheduledDays,
            // Kirim semua media yang bisa dipilih untuk ditambahkan
            'allMedia' => Media::where('is_approved', true)
                            ->with('client:id,company_name')
                            ->get(['id', 'title', 'duration']),
        ]);
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
