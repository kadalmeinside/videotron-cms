<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage_schedules');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'playlist_id' => 'required|exists:playlists,id',
            'videotron_id' => 'required|exists:videotrons,id',
            'start_time' => 'required|date',
            'end_time' => [
                'required',
                'date',
                'after:start_time',
                // Custom validation to prevent overlaps
                Rule::unique('schedules')->where(function ($query) {
                    return $query->where('videotron_id', $this->videotron_id)
                                 ->where(function($q) {
                                     $q->where(function($sub) {
                                         $sub->where('start_time', '<', $this->end_time)
                                             ->where('end_time', '>', $this->start_time);
                                     });
                                 });
                })
            ],
        ];
    }
}
