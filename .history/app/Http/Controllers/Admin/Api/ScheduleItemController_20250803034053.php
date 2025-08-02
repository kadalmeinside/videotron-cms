<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use App\Models\Videotron;
use App\Rules\NoScheduleOverlap;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Messaging\CloudMessage;

class ScheduleItemController extends Controller
{
    /**
     * Menyimpan item jadwal baru.
     */
    public function store(Request $request): JsonResponse
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

        // Panggil pemicu FCM setelah berhasil menyimpan
        $this->triggerFCMForSchedule($validated['schedule_id']);

        return response()->json(['message' => 'Item jadwal berhasil ditambahkan.'], 201);
    }

    /**
     * Memperbarui item jadwal yang sudah ada.
     */
    public function update(Request $request, ScheduleItem $scheduleItem): JsonResponse
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
        
        // Panggil pemicu FCM setelah berhasil memperbarui
        $this->triggerFCMForSchedule($scheduleItem->schedule_id);

        return response()->json(['message' => 'Item jadwal berhasil diperbarui.']);
    }

    /**
     * Menyalin semua item jadwal dari satu tanggal ke tanggal lain.
     */
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

        // Ambil jadwal yang sudah ada di tanggal tujuan untuk pengecekan tumpang tindih
        $existingTargetItems = $schedule->scheduleItems()
            ->whereDate('play_at', $targetDate)
            ->get();

        // Iterasi melalui setiap item yang akan disalin untuk memeriksa tumpang tindih
        foreach ($sourceItems as $itemToCopy) {
            $playTime = Carbon::parse($itemToCopy->play_at)->format('H:i:s');
            $newStartTime = Carbon::parse($targetDate . ' ' . $playTime);
            $newEndTime = $newStartTime->copy()->addSeconds((int) $itemToCopy->duration_in_seconds);

            // Bandingkan dengan setiap item yang sudah ada di tanggal tujuan
            foreach ($existingTargetItems as $existingItem) {
                $existingStartTime = Carbon::parse($existingItem->play_at);
                $existingEndTime = $existingStartTime->copy()->addSeconds((int) $existingItem->duration_in_seconds);

                // Kondisi untuk tumpang tindih
                if ($newStartTime < $existingEndTime && $newEndTime > $existingStartTime) {
                    return response()->json([
                        'message' => 'Operasi gagal. Jadwal yang akan disalin tumpang tindih dengan jadwal yang sudah ada di tanggal tujuan pada sekitar jam ' . $newStartTime->format('H:i')
                    ], 422); // 422 Unprocessable Entity
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

        // Panggil pemicu FCM setelah berhasil menyalin
        $this->triggerFCMForSchedule($schedule->id);

        return response()->json(['message' => 'Jadwal dari tanggal ' . $sourceDate . ' berhasil disalin ke ' . $targetDate . '.']);
    }

    /**
     * Menghapus item jadwal.
     */
    public function destroy(ScheduleItem $scheduleItem): JsonResponse
    {
        $scheduleId = $scheduleItem->schedule_id; // Simpan ID sebelum dihapus
        $scheduleItem->delete();
        
        // Panggil pemicu FCM setelah berhasil menghapus
        $this->triggerFCMForSchedule($scheduleId);

        return response()->json(['message' => 'Item jadwal berhasil dihapus.'], 200);
    }

    /**
     * Fungsi terpusat untuk mengirim notifikasi FCM ke semua perangkat
     * yang menggunakan schedule tertentu.
     */
    private function triggerFCMForSchedule($scheduleId)
    {
        // 1. Perbarui versi schedule untuk menandakan ada perubahan
        $schedule = Schedule::find($scheduleId);
        if ($schedule) {
            $schedule->touch(); // Ini akan memperbarui kolom `updated_at`
        }

        // 2. Cari semua videotron yang menggunakan schedule ini dan memiliki token FCM
        $videotronsToNotify = Videotron::where('schedule_id', $scheduleId)
                                       ->whereNotNull('fcm_token')
                                       ->get();

        if ($videotronsToNotify->isNotEmpty()) {
            $messaging = app('firebase.messaging');

            foreach ($videotronsToNotify as $videotron) {
                try {
                    // 3. Siapkan pesan data (notifikasi senyap)
                    $message = CloudMessage::withTarget('token', $videotron->fcm_token)
                        ->withData(['action' => 'force_sync']);

                    // 4. Kirim pesan
                    $messaging->send($message);

                    \Log::info("Pesan force_sync dikirim ke perangkat: {$videotron->name}");

                } catch (\Exception $e) {
                    \Log::error("Gagal mengirim FCM ke {$videotron->name}: " . $e->getMessage());
                }
            }
        }
    }
}
