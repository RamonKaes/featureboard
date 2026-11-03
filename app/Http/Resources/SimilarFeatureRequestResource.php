<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\FeatureRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FeatureRequest
 */
class SimilarFeatureRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'votesCount' => $this->votes_count,
        ];
    }
}
