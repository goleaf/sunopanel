<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TrackUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'sometimes', 
                'string', 
                'max:255', 
                Rule::unique('tracks')->ignore($this->route('track'))
            ],
            'url' => ['sometimes', 'url'],
            'cover_image' => ['nullable', 'url'],
            'genres' => ['nullable', 'array'],
            'genres.*' => ['exists:genres,id'],
            'playlists' => ['nullable', 'array'],
            'playlists.*' => ['exists:playlists,id'],
        ];
    }
} 