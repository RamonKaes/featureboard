<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FeatureRequest;
use App\Models\User;

class FeatureRequestPolicy
{
    public function vote(User $user, FeatureRequest $featureRequest): bool
    {
        return $featureRequest->user_id !== $user->id
            && $featureRequest->status->acceptsVotes();
    }

    public function update(User $user, FeatureRequest $featureRequest): bool
    {
        return $featureRequest->user_id === $user->id
            && $featureRequest->status->isEditable();
    }

    public function changeStatus(User $user, FeatureRequest $featureRequest): bool
    {
        return $user->is_admin;
    }
}
