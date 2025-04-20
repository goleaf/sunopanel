<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BulkTrackRequest extends FormRequest
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
            'bulk_tracks' => ['required', 'string', 'min:5'],
            // File upload rules
            'files.*' => 'required|file|mimes:mp3,wav,ogg|max:20000',
            'defaultGenreId' => 'nullable|exists:genres,id',
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
            'bulk_tracks.required' => 'Please provide track data for bulk upload.',
            'bulk_tracks.min' => 'The bulk tracks data is too short. Please provide valid track data.',
            'bulk_tracks.string' => 'Track data must be provided as a string in the format: Title|Audio URL|Image URL|Genres[|Duration]',
            // File upload messages
            'files.*.required' => 'Please select at least one file to upload.',
            'files.*.file' => 'The uploaded item must be a file.',
            'files.*.mimes' => 'The file must be an audio file (MP3, WAV, OGG).',
            'files.*.max' => 'The file size must not exceed 20MB.',
            'defaultGenreId.exists' => 'The selected genre does not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $bulkTracks = $this->input('bulk_tracks');
            if (empty($bulkTracks)) {
                return;
            }

            $lines = explode(PHP_EOL, str_replace("\r", "", $bulkTracks));
            $totalTracks = 0;
            
            foreach ($lines as $index => $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                
                $totalTracks++;
                $parts = explode('|', $line);
                
                if (count($parts) < 3) {
                    $validator->errors()->add(
                        'bulk_tracks',
                        'Line ' . ($index + 1) . ' has an invalid format. Expected format: Title|Audio URL|Image URL|Genres[|Duration]'
                    );
                    continue;
                }
                
                // Validate track title
                if (empty(trim($parts[0]))) {
                    $validator->errors()->add(
                        'bulk_tracks',
                        'Line ' . ($index + 1) . ': Track title cannot be empty.'
                    );
                }
                
                // Validate URLs
                if (filter_var($parts[1], FILTER_VALIDATE_URL) === false) {
                    $validator->errors()->add(
                        'bulk_tracks',
                        'Line ' . ($index + 1) . ': Audio URL is not valid.'
                    );
                }
                
                if (filter_var($parts[2], FILTER_VALIDATE_URL) === false) {
                    $validator->errors()->add(
                        'bulk_tracks',
                        'Line ' . ($index + 1) . ': Image URL is not valid.'
                    );
                }
            }
            
            if ($totalTracks === 0) {
                $validator->errors()->add(
                    'bulk_tracks',
                    'No valid tracks found. Please provide at least one track in the correct format.'
                );
            }
        });
    }
}
