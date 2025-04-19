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
        // If bulk_tracks is provided, make other fields optional
        if ($this->filled('bulk_tracks')) {
            return [
                'bulk_tracks' => ['required', 'string'],
                // Still include but make optional for UI form compatibility
                'title' => ['nullable', 'string', 'max:255'],
                'audio_url' => ['nullable', 'url'],
                'image_url' => ['nullable', 'url'],
                'duration' => ['nullable', 'string', 'max:10'],
                'genres' => ['nullable', 'string'],
                'genre_ids' => ['nullable', 'array'],
                'genre_ids.*' => ['exists:genres,id'],
                'playlists' => ['nullable', 'array'],
                'playlists.*' => ['exists:playlists,id'],
            ];
        }

        return [
            'title' => ['required', 'string', 'max:255', 'unique:tracks'],
            'audio_url' => ['required', 'url'],
            'image_url' => ['required', 'url'],
            'duration' => ['nullable', 'string', 'max:10'],
            // Accept either genres string or genre_ids array
            'genres' => ['required_without:genre_ids', 'string'],
            'genre_ids' => ['required_without:genres', 'array'],
            'genre_ids.*' => ['exists:genres,id'],
            'playlists' => ['nullable', 'array'],
            'playlists.*' => ['exists:playlists,id'],
            'bulk_tracks' => ['nullable', 'string'],
        ];
    }
} 