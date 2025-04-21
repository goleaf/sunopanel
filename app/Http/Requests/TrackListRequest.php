<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TrackListRequest extends FormRequest
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
            'genreFilter' => 'nullable|exists:genres,id',
            'perPage' => 'integer|in:5,10,15,25,50',
            'sortField' => 'string|in:title,artist,album,created_at,updated_at',
            'direction' => 'string|in:asc,desc',
            'trackIdToDelete' => 'nullable|exists:tracks,id',
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
            'genreFilter.exists' => 'The selected genre does not exist.',
            'perPage.in' => 'The selected page size is invalid.',
            'sortField.in' => 'The sort field is invalid.',
            'direction.in' => 'The sort direction is invalid.',
            'trackIdToDelete.exists' => 'The track to delete does not exist.',
        ];
    }
} 