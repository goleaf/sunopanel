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
                Rule::unique('tracks')->ignore($this->route('track')),
            ],
            'audio_url' => ['required', 'url'],
            'image_url' => ['required', 'url'],
            'duration' => ['nullable', 'string', 'max:10'],
            'genres' => ['nullable', 'required_without:genre_ids', 'string'],
            'genre_ids' => ['nullable', 'required_without:genres', 'array'],
            'genre_ids.*' => ['exists:genres,id'],
            'playlists' => ['nullable', 'array'],
            'playlists.*' => ['exists:playlists,id'],
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
            'genres.string' => 'Genres must be a comma-separated string.',
            'genres.required_without' => 'Please provide genres either as a string or an array of IDs.',
            'genre_ids.array' => 'Genre IDs must be an array.',
            'genre_ids.required_without' => 'Please provide genres either as a string or an array of IDs.',
            'genre_ids.*.exists' => 'One or more selected genres do not exist.',
        ];
    }
}
