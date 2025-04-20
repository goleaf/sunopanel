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
            'title' => 'required|string|max:255',
            'artist' => 'nullable|string|max:255',
            'album' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:10',
            'selectedGenres' => 'nullable|array',
            'selectedGenres.*' => 'exists:genres,id',
            'audio_url' => 'required|string',
            'image_url' => 'nullable|string',
            'genres' => 'nullable|string',
            'genre_ids' => 'nullable|array',
            'genre_ids.*' => 'exists:genres,id',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The track title is required.',
            'title.max' => 'The track title cannot exceed 255 characters.',
            'artist.max' => 'The artist name cannot exceed 255 characters.',
            'album.max' => 'The album name cannot exceed 255 characters.',
            'duration.max' => 'The duration format is invalid.',
            'audio_url.required' => 'The audio URL is required.',
            'selectedGenres.*.exists' => 'One or more selected genres do not exist in our system.',
            'genre_ids.*.exists' => 'One or more selected genres do not exist in our system.',
        ];
    }
}
