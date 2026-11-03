<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FeatureRequestStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ChangeStatus
{
    /**
     * Move the feature request to a new status along an allowed transition.
     */
    public function handle(User $admin, FeatureRequest $featureRequest, FeatureRequestStatus $status): FeatureRequest
    {
        return DB::transaction(function () use ($admin, $featureRequest, $status) {
            $locked = FeatureRequest::query()->lockForUpdate()->findOrFail($featureRequest->id);

            Gate::forUser($admin)->authorize('changeStatus', $locked);

            if (! $locked->status->canTransitionTo($status)) {
                throw new InvalidStatusTransition($locked->status, $status);
            }

            $locked->status = $status;
            $locked->save();

            return $locked;
        });
    }
}
