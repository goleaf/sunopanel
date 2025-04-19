<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TrackStoreRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255', 'unique:tracks'],
            'audio_url' => ['required', 'url'],
            'image_url' => ['required', 'url'],
            'duration' => ['nullable', 'string', 'max:10'],
            'genres' => ['required', 'string'],
            'playlists' => ['nullable', 'array'],
            'playlists.*' => ['exists:playlists,id'],
            'bulk_tracks' => ['nullable', 'string'],
        ];
    }
} 