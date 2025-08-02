<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use App\Models\Videotron; // <-- Tambahkan import ini
use App\Rules\NoScheduleOverlap;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Messaging\CloudMessage; // <-- Tambahkan import ini

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

        // Panggil pemicu FCM setelah berhasil menyimpan
        $this->triggerFCMForSchedule($validated['schedule_id']);

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
        
        // Panggil pemicu FCM setelah berhasil memperbarui
        $this->triggerFCMForSchedule($scheduleItem->schedule_id);

        return response()->json(['message' => 'Item jadwal berhasil diperbarui.']);
    }

    public function copyDate(Request $request, Schedule $schedule)
    {
        // ... (logika validasi dan pengecekan tumpang tindih tidak berubah)

        try {
            DB::transaction(function () use ($sourceItems, $targetDate, $schedule) {
                foreach ($sourceItems as $itemToCopy) {
                    // ... (logika penyalinan tidak berubah)
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
    public function destroy(ScheduleItem $scheduleItem)
    {
        $scheduleId = $scheduleItem->schedule_id; // Simpan ID sebelum dihapus
        $scheduleItem->delete();
        
        // Panggil pemicu FCM setelah berhasil menghapus
        $this->triggerFCMForSchedule($scheduleId);

        return response()->noContent();
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
