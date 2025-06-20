<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\PlayLog;
use App\Models\Videotron;
use Illuminate\Http\Request;
use App\Exports\PlayLogsExport;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view_reports'); 

        $query = PlayLog::with(['media.client', 'videotron', 'playlist'])->latest('played_at');

        if ($request->filled('date_start')) {
            $query->whereDate('played_at', '>=', $request->date_start);
        }
        if ($request->filled('date_end')) {
            $query->whereDate('played_at', '<=', $request->date_end);
        }
        if ($request->filled('videotron_id')) {
            $query->where('videotron_id', $request->videotron_id);
        }
        if ($request->filled('client_id')) {
            $query->whereHas('media.client', function ($q) use ($request) {
                $q->where('id', $request->client_id);
            });
        }
        
        $logs = $query->paginate(10)->onEachSide(3)->withQueryString();

        return Inertia::render('Admin/Reports/Index', [
            'logs' => $logs,
            'filters' => $request->only(['date_start', 'date_end', 'videotron_id', 'client_id']),
            'allVideotrons' => Videotron::orderBy('name')->get(['id', 'name']),
            'allClients' => Client::orderBy('company_name')->get(['id', 'company_name']),
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('view_reports');

        $filters = $request->only(['date_start', 'date_end', 'videotron_id', 'client_id']);
        $fileName = 'laporan-tayang-' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new PlayLogsExport($filters), $fileName);
    }
}
