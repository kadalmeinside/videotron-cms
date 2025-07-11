<?php

namespace App\Rules;

use App\Models\Media;
use App\Models\ScheduleItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Support\Carbon;

class NoScheduleOverlap implements DataAwareRule, InvokableRule
{
    /**
     * Data dari request validasi.
     * @var array
     */
    protected $data = [];

    /**
     * ID dari item yang sedang diupdate (opsional).
     * @var int|null
     */
    protected $ignoreId = null;

    /**
     * Set data dari request.
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set ID yang akan diabaikan saat pengecekan (untuk update).
     */
    public function ignore($id): static
    {
        $this->ignoreId = $id;
        return $this;
    }

    /**
     * Jalankan aturan validasi.
     */
    public function __invoke(string $attribute, mixed $value, Closure $fail): void
    {
        $media = Media::find($this->data['media_id']);
        if (!$media) {
            return;
        }
        $duration = $media->duration;

        $startTime = Carbon::createFromFormat('Y-m-d H:i', $this->data['schedule_date'] . ' ' . $this->data['play_time']);
        $endTime = $startTime->copy()->addSeconds((int) $duration);

        $query = ScheduleItem::where('videotron_id', $this->data['videotron_id'])
            ->whereDate('play_at', $this->data['schedule_date'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('play_at', '<', $endTime)
                  ->whereRaw('TIMESTAMPADD(SECOND, duration_in_seconds, play_at) > ?', [$startTime]);
            });
            
        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail('Slot waktu ini tumpang tindih dengan jadwal lain yang sudah ada.');
        }
    }
}
