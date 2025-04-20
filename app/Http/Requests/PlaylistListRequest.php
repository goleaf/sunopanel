<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PlaylistListRequest extends FormRequest
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
            'search' => 'nullable|string|max:255',
            'sortField' => 'string|in:title,created_at,updated_at,track_count',
            'direction' => 'string|in:asc,desc',
            'perPage' => 'integer|in:5,10,15,25,50',
            'genreFilter' => 'nullable|exists:genres,id',
            'playlistId' => 'nullable|exists:playlists,id',
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
            'sortField.in' => 'The sort field is invalid.',
            'direction.in' => 'The sort direction is invalid.',
            'perPage.in' => 'The page size is invalid.',
            'genreFilter.exists' => 'The selected genre does not exist.',
            'playlistId.exists' => 'The selected playlist does not exist.',
        ];
    }
} 