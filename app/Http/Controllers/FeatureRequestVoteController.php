<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CastVote;
use App\Actions\RetractVote;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

class FeatureRequestVoteController extends Controller
{
    /**
     * Cast the current user's vote. The action checks the permission on the locked row.
     */
    public function store(FeatureRequest $featureRequest, CastVote $castVote, #[CurrentUser] User $user): RedirectResponse
    {
        $castVote->handle($user, $featureRequest);

        return back();
    }

    /**
     * Retract the current user's vote.
     */
    public function destroy(FeatureRequest $featureRequest, RetractVote $retractVote, #[CurrentUser] User $user): RedirectResponse
    {
        $retractVote->handle($user, $featureRequest);

        return back();
    }
}
