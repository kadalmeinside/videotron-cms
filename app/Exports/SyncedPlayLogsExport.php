<?php

namespace App\Exports;

use App\Models\SyncedPlayLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SyncedPlayLogsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
    * @return \Illuminate\Database\Query\Builder
    */
    public function query()
    {
        // Logika query ini persis sama dengan di LogController
        $query = SyncedPlayLog::query()->with('videotron:id,name')->latest('logged_at');

        if (!empty($this->filters['date'])) {
            $query->whereDate('logged_at', $this->filters['date']);
        }
        if (!empty($this->filters['videotron_id'])) {
            $query->where('videotron_id', $this->filters['videotron_id']);
        }
        
        return $query;
    }

    /**
    * @return array
    */
    public function headings(): array
    {
        return [
            'Waktu Tercatat',
            'Nama Videotron',
            'Tipe Event',
            'Pesan Log',
        ];
    }

    /**
    * @param SyncedPlayLog $log
    * @return array
    */
    public function map($log): array
    {
        return [
            $log->logged_at,
            $log->videotron?->name,
            $log->event_type,
            $log->message,
        ];
    }
}
