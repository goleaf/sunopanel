<?php

declare(strict_types=1);

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
                'required', 
                'string', 
                'max:255', 
                Rule::unique('tracks')->ignore($this->route('track'))
            ],
            'audio_url' => ['required', 'url'],
            'image_url' => ['required', 'url'],
            'duration' => ['nullable', 'string', 'max:10'],
            'genres' => ['required', 'string'],
            'playlists' => ['nullable', 'array'],
            'playlists.*' => ['exists:playlists,id'],
        ];
    }
} 