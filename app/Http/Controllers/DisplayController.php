<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DisplayController extends Controller
{
    /**
     * Menampilkan halaman display lobi.
     */
    public function index()
    {
        return Inertia::render('Public/Display', [
            'pageTitle' => 'Informasi & Jadwal',
        ]);
    }
}
