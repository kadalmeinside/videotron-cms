<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Http\Requests\Admin\UpdateMediaRequest;
use App\Models\Client;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MediaController extends Controller
{

    private function getMediaDataForEdit(Media $media)
    {
        return [
            'id' => $media->id,
            'title' => $media->title,
            'client_id' => $media->client_id,
            'source_type' => $media->source_type,
            'source_value' => $media->source_value,
            'duration' => $media->duration,
            'is_approved' => $media->is_approved,
            'running_text' => $media->running_text,
            'theme_type' => $media->theme_type,
            'theme_color_1' => $media->theme_color_1,
            'theme_color_2' => $media->theme_color_2,
            // URL untuk preview, bukan path
            'preview_url' => $media->source_type === 'local' && $media->source_value ? Storage::url($media->source_value) : null,
            'top_banner_url' => $media->top_banner_path ? Storage::url($media->top_banner_path) : null,
            'bottom_banner_url' => $media->bottom_banner_path ? Storage::url($media->bottom_banner_path) : null,
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Media::class);
        $query = Media::with('client')->latest();

        if ($request->filled('search')) {
            $query->where('title', 'LIKE', '%' . $request->search . '%');
        }

        return Inertia::render('Admin/Media/Index', [
            'mediaList' => $query->paginate(10)->withQueryString()->through(fn($media) => [
                'id' => $media->id,
                'title' => $media->title,
                'client' => $media->client,
                'source_type' => $media->source_type,
                
                'preview_url' => $media->source_type === 'local' && $media->source_value ? Storage::url($media->source_value) : null,
                // Data baru yang kita butuhkan di frontend
                'is_approved' => $media->is_approved,
                'top_banner_url' => $media->top_banner_path ? Storage::url($media->top_banner_path) : null,
                'bottom_banner_url' => $media->bottom_banner_path ? Storage::url($media->bottom_banner_path) : null,

                'full_data_for_edit' => $this->getMediaDataForEdit($media),
            ]),
            'filters' => $request->only(['search']),
            'clients' => Client::orderBy('company_name')->get(['id', 'company_name']),
            'can' => ['manage_media' => $request->user()->can('manage_media')],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMediaRequest $request)
    {
        $data = $request->validated();

        // 1. Proses semua file upload dan simpan path-nya ke field yang benar
        if ($request->source_type === 'local' && $request->hasFile('source_file')) {
            $data['source_value'] = $request->file('source_file')->store('media/main', 'public');
        }
        if ($request->hasFile('top_banner_file')) {
            $data['top_banner_path'] = $request->file('top_banner_file')->store('media/banners', 'public');
        }
        if ($request->hasFile('bottom_banner_file')) {
            $data['bottom_banner_path'] = $request->file('bottom_banner_file')->store('media/banners', 'public');
        }

        // 2. HAPUS field-field virtual dari array data sebelum create
        unset($data['source_file']);
        unset($data['top_banner_file']);
        unset($data['bottom_banner_file']);

        // 3. Buat record di database dengan data yang sudah bersih
        Media::create($data);

        return Redirect::route('admin.media.index')->with([
            'message' => 'Media berhasil ditambahkan.', 'type' => 'success'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMediaRequest $request, Media $medium)
    {
        $data = $request->validated();

        if ($request->hasFile('source_file') && $request->source_type === 'local') {
            if ($medium->source_type === 'local' && $medium->source_value) {
                Storage::disk('public')->delete($medium->source_value);
            }
            $data['source_value'] = $request->file('source_file')->store('media/main', 'public');
        }

        if ($request->hasFile('top_banner_file')) {
            if ($medium->top_banner_path) {
                Storage::disk('public')->delete($medium->top_banner_path);
            }
            $data['top_banner_path'] = $request->file('top_banner_file')->store('media/banners', 'public');
        } else if ($request->input('top_banner_file') === null) {
            // Jika input file kosong (misal karena tombol clear di klik), hapus path lama
             if ($medium->top_banner_path) {
                Storage::disk('public')->delete($medium->top_banner_path);
            }
            $data['top_banner_path'] = null;
        }


        if ($request->hasFile('bottom_banner_file')) {
            if ($medium->bottom_banner_path) {
                Storage::disk('public')->delete($medium->bottom_banner_path);
            }
            $data['bottom_banner_path'] = $request->file('bottom_banner_file')->store('media/banners', 'public');
        } else if ($request->input('bottom_banner_file') === null) {
            if ($medium->bottom_banner_path) {
                Storage::disk('public')->delete($medium->bottom_banner_path);
            }
            $data['bottom_banner_path'] = null;
        }

        // Hapus field virtual sebelum update
        unset($data['source_file'], $data['top_banner_file'], $data['bottom_banner_file']);

        $medium->update($data);

        return Redirect::route('admin.media.index')->with(['message' => 'Media berhasil diperbarui.', 'type' => 'success']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Media $medium) // Laravel otomatis mengubah 'media' menjadi 'medium'
    {
        $this->authorize('delete', $medium);

        // Hapus file dari storage sebelum menghapus record DB
        if ($medium->source_type === 'local' && $medium->source_value) {
            Storage::disk('public')->delete($medium->source_value);
        }
        if ($medium->top_banner_path) {
            Storage::disk('public')->delete($medium->top_banner_path);
        }
        if ($medium->bottom_banner_path) {
            Storage::disk('public')->delete($medium->bottom_banner_path);
        }

        $medium->delete();

        return Redirect::route('admin.media.index')->with([
            'message' => 'Media berhasil dihapus.', 'type' => 'success'
        ]);
    }
}
