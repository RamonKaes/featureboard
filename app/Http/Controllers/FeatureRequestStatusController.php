<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ChangeStatus;
use App\Http\Requests\ChangeStatusRequest;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class FeatureRequestStatusController extends Controller
{
    /**
     * Change the status. The action checks permission and transition on the locked row.
     */
    public function __invoke(
        ChangeStatusRequest $request,
        FeatureRequest $featureRequest,
        ChangeStatus $changeStatus,
        #[CurrentUser] User $user,
    ): RedirectResponse {
        $updated = $changeStatus->handle($user, $featureRequest, $request->status());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Status geändert: {$updated->status->label()}"]);

        return back();
    }
}
