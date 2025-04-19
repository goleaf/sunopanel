<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PlaylistUpdateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('playlists')->ignore($this->playlist),
            ],
            'description' => 'nullable|string',
            'cover_image' => 'nullable|url',
            'genre_id' => 'nullable|exists:genres,id',
            'tracks' => 'nullable|array',
            'tracks.*' => 'exists:tracks,id',
        ];
    }
} 