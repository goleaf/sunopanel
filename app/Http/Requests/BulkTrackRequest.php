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
            'bulk_tracks' => ['required', 'string', 'min:5']
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
        ];
    }
    
    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $bulkTracks = $this->input('bulk_tracks');
            if (empty($bulkTracks)) {
                return;
            }
            
            $lines = explode(PHP_EOL, $bulkTracks);
            foreach ($lines as $index => $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                
                $parts = explode('|', $line);
                if (count($parts) < 3) {
                    $validator->errors()->add(
                        'bulk_tracks',
                        "Line " . ($index + 1) . " has an invalid format. Expected format: Title|Audio URL|Image URL|Genres[|Duration]"
                    );
                }
            }
        });
    }
} 