<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Media;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class MediaApprovalController extends Controller
{
    public function index()
    {
        $this->authorize('approve', Media::class); // Menggunakan MediaPolicy
        
        return Inertia::render('Admin/Media/Approvals', [
            'pendingMedia' => Media::where('is_approved', false)
                                ->with('client')
                                ->latest()
                                ->paginate(10),
        ]);
    }

    public function approve(Media $medium)
    {
        $this->authorize('approve', $medium);
        $medium->update(['is_approved' => true]);
        return Redirect::back()->with(['message' => 'Media berhasil disetujui.', 'type' => 'success']);
    }

    public function reject(Media $medium)
    {
        $this->authorize('approve', $medium);
        app(MediaController::class)->destroy($medium);
        return Redirect::back()->with(['message' => 'Media berhasil ditolak dan dihapus.', 'type' => 'success']);
    }
}
