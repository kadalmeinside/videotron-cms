<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePlaylistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage_playlists');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // Validasi diubah untuk menerima struktur baru
            'media' => 'present|array', // Harus ada, boleh kosong
            'media.*.id' => 'required|integer|exists:media,id', // Setiap item harus punya ID media yang valid
            'media.*.play_order' => 'required|integer', // Setiap item harus punya urutan
        ];
    }
}
