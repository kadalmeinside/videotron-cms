<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->medium);
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
            
            'source_value' => 'required_if:source_type,youtube,vimeo|nullable|string|max:255|url',
            
            // Validasi untuk field "virtual" dari form upload.
            // Bersifat opsional (nullable) saat update.
            'source_file' => 'nullable|file|mimes:mp4,mov,jpg,jpeg,png,webp|max:20480',
            'top_banner_file' => 'nullable|image|max:2048',
            'bottom_banner_file' => 'nullable|image|max:2048',

            'duration' => 'required|integer|min:1',
            'running_text' => 'nullable|string',
        ];
    }
}
