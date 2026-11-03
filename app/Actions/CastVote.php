<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CastVote
{
    /**
     * Add the user's vote. Voting again has no further effect.
     */
    public function handle(User $user, FeatureRequest $featureRequest): FeatureRequest
    {
        return DB::transaction(function () use ($user, $featureRequest) {
            // The row lock serializes all votes on this request and keeps the status stable until commit.
            $locked = FeatureRequest::query()->lockForUpdate()->findOrFail($featureRequest->id);

            Gate::forUser($user)->authorize('vote', $locked);

            if ($locked->votes()->firstOrCreate(['user_id' => $user->id])->wasRecentlyCreated) {
                FeatureRequest::withoutTimestamps(fn () => $locked->increment('votes_count'));
            }

            return $locked;
        });
    }
}
