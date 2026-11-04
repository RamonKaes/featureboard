<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\FeatureRequestStatus;
use DomainException;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class InvalidStatusTransition extends DomainException implements ShouldntReport
{
    public function __construct(FeatureRequestStatus $from, FeatureRequestStatus $to)
    {
        parent::__construct("A feature request cannot change from [{$from->value}] to [{$to->value}].");
    }

    /**
     * Return to the page with an explanation. This happens when the status changed after the page loaded.
     */
    public function render(): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => 'Dieser Statuswechsel ist nicht erlaubt.']);

        return back();
    }
}
