<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\SyncedPlayLogsExport;
use App\Models\SyncedPlayLog;
use App\Models\Videotron;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;

class LogController extends Controller
{
    /**
     * Menampilkan halaman daftar log pemutaran.
     */
    public function index(Request $request)
    {
        $this->authorize('view_reports');

        $query = SyncedPlayLog::with('videotron:id,name,uuid')->latest('logged_at');

        if ($request->filled('videotron_id')) {
            $query->where('videotron_id', $request->videotron_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('logged_at', $request->date);
        }

        $logs = $query->paginate(30)->withQueryString();

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'allVideotrons' => Videotron::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['videotron_id', 'date']),
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('view_reports');

        $filters = $request->only(['date', 'videotron_id']);
        $fileName = 'laporan-log-aktivitas-' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new SyncedPlayLogsExport($filters), $fileName);
    }
}
