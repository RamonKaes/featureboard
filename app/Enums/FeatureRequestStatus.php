<?php

declare(strict_types=1);

namespace App\Enums;

enum FeatureRequestStatus: string
{
    case New = 'new';
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Declined = 'declined';
}
