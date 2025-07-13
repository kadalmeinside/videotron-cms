<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\ScheduleItem;
use App\Rules\NoScheduleOverlap;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ScheduleItemController extends Controller
{
    /**
     * Menyimpan item jadwal baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'media_id' => 'required|exists:media,id',
            'schedule_date' => 'required|date_format:Y-m-d',
            'play_time' => ['required', 'date_format:H:i', new NoScheduleOverlap],
        ]);

        // Ambil durasi dari media yang dipilih
        $duration = Media::find($validated['media_id'])->duration;

        // Gabungkan tanggal dan waktu menjadi satu timestamp
        $playAt = Carbon::createFromFormat('Y-m-d H:i', $validated['schedule_date'] . ' ' . $validated['play_time']);

        ScheduleItem::create([
            'schedule_id' => $validated['schedule_id'],
            'media_id' => $validated['media_id'],
            'play_at' => $playAt,
            'duration_in_seconds' => $duration,
        ]);

        return response()->json(['message' => 'Item jadwal berhasil ditambahkan.'], 201);
    }

    /**
     * Memperbarui item jadwal yang sudah ada.
     */
    public function update(Request $request, ScheduleItem $scheduleItem)
    {
        $validated = $request->validate([
            'media_id' => 'required|exists:media,id',
            'schedule_date' => 'required|date_format:Y-m-d',
            'play_time' => ['required', 'date_format:H:i', (new NoScheduleOverlap)->ignore($scheduleItem->id)],
        ]);

        $duration = Media::find($validated['media_id'])->duration;
        $playAt = Carbon::createFromFormat('Y-m-d H:i', $validated['schedule_date'] . ' ' . $validated['play_time']);

        $scheduleItem->update([
            'media_id' => $validated['media_id'],
            'play_at' => $playAt,
            'duration_in_seconds' => $duration,
        ]);

        return response()->json(['message' => 'Item jadwal berhasil diperbarui.']);
    }

    /**
     * Menghapus item jadwal.
     */
    public function destroy(ScheduleItem $scheduleItem)
    {
        $scheduleItem->delete();

        return response()->noContent();
    }
}
