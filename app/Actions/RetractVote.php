<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RetractVote
{
    /**
     * Remove the user's vote. Retracting without a vote has no effect.
     */
    public function handle(User $user, FeatureRequest $featureRequest): FeatureRequest
    {
        return DB::transaction(function () use ($user, $featureRequest) {
            $locked = FeatureRequest::query()->lockForUpdate()->findOrFail($featureRequest->id);

            if ($locked->votes()->where('user_id', $user->id)->delete() > 0) {
                FeatureRequest::withoutTimestamps(fn () => $locked->decrement('votes_count'));
            }

            return $locked;
        });
    }
}
