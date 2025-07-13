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

        $schedules = Schedule::withCount('scheduleItems')->latest()->paginate(10);
        
        return Inertia::render('Admin/Schedules/Index', [
            'schedules' => $schedules,
            'can' => ['manage_schedules' => auth()->user()->can('manage_schedules')]
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
    public function store(Request $request)
    {
        $this->authorize('manage_schedules');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:schedules,name',
            'description' => 'nullable|string',
        ]);

        Schedule::create($validated);

        return Redirect::route('admin.schedules.index')->with('success', 'Template jadwal berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Schedule $schedule)
    {
        $this->authorize('manage_schedules');

        $items = $schedule->scheduleItems()->with('media:id,title,duration')->orderBy('play_at')->get();

        $scheduledDays = $items->groupBy(function ($item) {
            return $item->play_at->format('Y-m-d');
        });

        return Inertia::render('Admin/Schedules/Builder', [
            'schedule' => $schedule,
            'scheduledDays' => $scheduledDays,
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
    public function update(Request $request, Schedule $schedule)
    {
        $this->authorize('manage_schedules');
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('schedules')->ignore($schedule->id)],
            'description' => 'nullable|string',
        ]);

        $schedule->update($validated);

        return Redirect::route('admin.schedules.index')->with('success', 'Template jadwal berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Schedule $schedule)
    {
        $this->authorize('manage_schedules');
        $schedule->delete();
        return Redirect::route('admin.schedules.index')->with('success', 'Template jadwal berhasil dihapus.');
    }
}
