<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteUser
{
    /**
     * Delete the user and lower the vote counts that the database cascade cannot maintain.
     */
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user) {
            // The lock blocks new votes by this user, so no vote escapes the count below.
            User::query()->lockForUpdate()->findOrFail($user->id);

            $votedFor = $user->votes()->pluck('feature_request_id');

            $user->delete();

            FeatureRequest::withoutTimestamps(
                fn () => FeatureRequest::query()->whereKey($votedFor)->decrement('votes_count'),
            );
        }, attempts: 3);
    }
}
