<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage_media');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'source_type' => 'required|in:local,youtube,vimeo',
            'source_value' => ['required_if:source_type,youtube,vimeo', 'nullable', 'string', 'max:255', 'url'],
            
            // Gunakan nama field yang jelas untuk upload, misal '..._file'
            'source_file' => ['required_if:source_type,local', 'nullable', 'file', 'mimes:mp4,mov,jpg,jpeg,png,webp'],
            'top_banner_file' => 'nullable|image|max:2048',
            'bottom_banner_file' => 'nullable|image|max:2048',

            'duration' => 'required|integer|min:1',
            'running_text' => 'nullable|string',
            'theme_type' => 'required|in:solid,gradient',
            'theme_color_1' => 'required|string|max:7',
            'theme_color_2' => 'nullable|string|max:7',
        ];
    }
}
