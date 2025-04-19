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
                'genre_ids.*' => ['nullable', 'exists:genres,id'],
                'playlists' => ['nullable', 'array'],
                'playlists.*' => ['nullable', 'exists:playlists,id'],
            ];
        }

        return [
            'title' => ['required', 'string', 'max:255', 'unique:tracks'],
            'audio_url' => ['required', 'url'],
            'image_url' => ['required', 'url'],
            'duration' => ['nullable', 'string', 'max:10'],
            // Make genres validation more flexible
            'genres' => ['nullable', 'string', 'required_without:genre_ids'],
            'genre_ids' => ['nullable', 'array', 'required_without:genres'],
            'genre_ids.*' => ['exists:genres,id'],
            'playlists' => ['nullable', 'array'],
            'playlists.*' => ['exists:playlists,id'],
            'bulk_tracks' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The track title is required.',
            'title.unique' => 'A track with this title already exists.',
            'audio_url.required' => 'The audio URL is required.',
            'audio_url.url' => 'The audio URL must be a valid URL.',
            'image_url.required' => 'The image URL is required.',
            'image_url.url' => 'The image URL must be a valid URL.',
            'genres.required_without' => 'Either genres or genre IDs must be provided.',
            'genre_ids.required_without' => 'Either genres or genre IDs must be provided.',
            'genres.string' => 'Genres must be a comma-separated string.',
            'genre_ids.array' => 'Genre IDs must be an array.',
            'genre_ids.*.exists' => 'One or more selected genres do not exist.',
        ];
    }
}
