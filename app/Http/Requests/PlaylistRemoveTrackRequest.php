<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PlaylistRemoveTrackRequest extends FormRequest
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
            'track_id' => 'sometimes|exists:tracks,id',
            'selectedTracks.*' => 'exists:tracks,id',
            'trackId' => 'exists:tracks,id',
            'orderedTracks.*' => 'exists:tracks,id',
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
            'track_id.exists' => 'The selected track does not exist.',
            'selectedTracks.*.exists' => 'One or more selected tracks do not exist.',
            'trackId.exists' => 'The track does not exist.',
            'orderedTracks.*.exists' => 'One or more ordered tracks do not exist.',
        ];
    }
}
