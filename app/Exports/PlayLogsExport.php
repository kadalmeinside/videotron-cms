<?php

namespace App\Exports;

use App\Models\PlayLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PlayLogsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function query()
    {
        // Logika query ini persis sama dengan di ReportController
        $query = PlayLog::query()->with(['media.client', 'videotron', 'playlist'])->latest('played_at');

        if (!empty($this->filters['date_start'])) {
            $query->whereDate('played_at', '>=', $this->filters['date_start']);
        }
        if (!empty($this->filters['date_end'])) {
            $query->whereDate('played_at', '<=', $this->filters['date_end']);
        }
        if (!empty($this->filters['videotron_id'])) {
            $query->where('videotron_id', $this->filters['videotron_id']);
        }
        if (!empty($this->filters['client_id'])) {
            $query->whereHas('media.client', function ($q) {
                $q->where('id', $this->filters['client_id']);
            });
        }
        
        return $query;
    }

    /**
    * @return array
    */
    public function headings(): array
    {
        return [
            'Waktu Tayang',
            'Judul Media',
            'Klien',
            'Playlist',
            'Videotron',
            'Durasi (detik)',
        ];
    }

    /**
    * @param PlayLog $log
    * @return array
    */
    public function map($log): array
    {
        return [
            $log->played_at,
            $log->media?->title,
            $log->media?->client?->company_name,
            $log->playlist?->name,
            $log->videotron?->name,
            $log->media?->duration,
        ];
    }
}
