<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use App\Rules\NoScheduleOverlap;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        $duration = Media::find($validated['media_id'])->duration;

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

    public function copyDate(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'source_date' => 'required|date_format:Y-m-d',
            'target_date' => 'required|date_format:Y-m-d|different:source_date',
        ]);

        $sourceDate = $validated['source_date'];
        $targetDate = $validated['target_date'];

        $sourceItems = $schedule->scheduleItems()
            ->whereDate('play_at', $sourceDate)
            ->get();

        if ($sourceItems->isEmpty()) {
            return response()->json(['message' => 'Tidak ada item untuk disalin dari tanggal sumber.'], 404);
        }

        $existingTargetItems = $schedule->scheduleItems()
            ->whereDate('play_at', $targetDate)
            ->get();

        foreach ($sourceItems as $itemToCopy) {
            $playTime = Carbon::parse($itemToCopy->play_at)->format('H:i:s');
            $newStartTime = Carbon::parse($targetDate . ' ' . $playTime);
            
            $newEndTime = $newStartTime->copy()->addSeconds((int) $itemToCopy->duration_in_seconds);

            foreach ($existingTargetItems as $existingItem) {
                $existingStartTime = Carbon::parse($existingItem->play_at);
                $existingEndTime = $existingStartTime->copy()->addSeconds((int) $existingItem->duration_in_seconds);

                if ($newStartTime < $existingEndTime && $newEndTime > $existingStartTime) {
                    return response()->json([
                        'message' => 'Operasi gagal. Jadwal yang akan disalin tumpang tindih dengan jadwal yang sudah ada di tanggal tujuan pada sekitar jam ' . $newStartTime->format('H:i')
                    ], 422);
                }
            }
        }

        try {
            DB::transaction(function () use ($sourceItems, $targetDate, $schedule) {
                foreach ($sourceItems as $itemToCopy) {
                    $playTime = Carbon::parse($itemToCopy->play_at)->format('H:i:s');
                    $newPlayAt = Carbon::parse($targetDate . ' ' . $playTime);

                    ScheduleItem::create([
                        'schedule_id' => $schedule->id,
                        'media_id' => $itemToCopy->media_id,
                        'play_at' => $newPlayAt,
                        'duration_in_seconds' => $itemToCopy->duration_in_seconds,
                    ]);
                }
            });
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Terjadi kesalahan internal saat menyalin jadwal.'], 500);
        }

        return response()->json(['message' => 'Jadwal dari tanggal ' . $sourceDate . ' berhasil disalin ke ' . $targetDate . '.']);
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
