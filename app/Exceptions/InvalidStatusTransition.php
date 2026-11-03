<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\FeatureRequestStatus;
use DomainException;

class InvalidStatusTransition extends DomainException
{
    public function __construct(FeatureRequestStatus $from, FeatureRequestStatus $to)
    {
        parent::__construct("A feature request cannot change from [{$from->value}] to [{$to->value}].");
    }
}
