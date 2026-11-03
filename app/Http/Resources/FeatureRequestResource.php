<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\FeatureRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FeatureRequest
 */
class FeatureRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'acceptsVotes' => $this->status->acceptsVotes(),
            'votesCount' => $this->votes_count,
            'hasVoted' => (bool) $this->getAttribute('has_voted'),
            'authorName' => $this->author?->name,
            'createdAt' => $this->created_at?->toIso8601String(),
            'can' => [
                'vote' => $request->user()?->can('vote', $this->resource) ?? false,
                'update' => $request->user()?->can('update', $this->resource) ?? false,
            ],
        ];
    }
}
