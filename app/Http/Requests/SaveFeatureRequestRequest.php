<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveFeatureRequestRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required', 'string', 'min:5', 'max:120',
                // Invisible characters pass min:5 and leave a link nobody can see or click.
                'regex:/(?:[\p{L}\p{N}].*){3}/su',
                // Direction overrides make a title read as something else.
                'not_regex:/[\x{202A}-\x{202E}\x{2066}-\x{2069}]/u',
            ],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.regex' => 'Titel muss mindestens 3 Buchstaben oder Ziffern enthalten.',
            'title.not_regex' => 'Titel darf keine Steuerzeichen für die Schreibrichtung enthalten.',
        ];
    }
}
