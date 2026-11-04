<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\FeatureRequest;
use Illuminate\Http\Request;

/**
 * @mixin FeatureRequest
 */
class SimilarFeatureRequestResource extends FeatureRequestSummaryResource
{
    /**
     * Whoever finds their wish in the hint can vote for it right there.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'acceptsVotes' => $this->status->acceptsVotes(),
            'hasVoted' => (bool) $this->getAttribute('has_voted'),
            'can' => [
                'vote' => $request->user()?->can('vote', $this->resource) ?? false,
            ],
        ];
    }
}
