<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Videotron;
use Inertia\Inertia;

class MonitoringController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Monitoring/Index', [
            'allVideotrons' => Videotron::get(['name', 'id']),
        ]);
    }
}
