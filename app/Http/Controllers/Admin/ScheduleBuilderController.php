<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\ScheduleItem;
use App\Models\Videotron;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ScheduleBuilderController extends Controller
{
    /**
     * Menampilkan halaman visual untuk membuat jadwal.
     */
    public function index(Request $request)
    {
        // Ganti dengan permission yang sesuai jika perlu
        $this->authorize('manage_schedules'); 

        return Inertia::render('Admin/Schedules/Builder', [
            // Kirim daftar semua videotron untuk pilihan dropdown
            'allSchedules' => Schedule::orderBy('name')->get(['id', 'name']),
            
            // Kirim daftar semua media yang sudah disetujui untuk di-drag-and-drop
            'allMedia' => Media::where('is_approved', true)
                            ->with('client:id,company_name') // Ambil data klien untuk info tambahan
                            ->get(['id', 'title', 'duration', 'client_id', 'source_type', 'source_value']),
        ]);
    }

    
}
