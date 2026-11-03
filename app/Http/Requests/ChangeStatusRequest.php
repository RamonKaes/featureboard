<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\FeatureRequestStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(FeatureRequestStatus::class)],
        ];
    }

    public function status(): FeatureRequestStatus
    {
        return FeatureRequestStatus::from($this->string('status')->toString());
    }
}
