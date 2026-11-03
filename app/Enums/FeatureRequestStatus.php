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

    /**
     * The statuses this status may change to. This is the single source of truth for the workflow.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Planned, self::Declined],
            self::Planned => [self::InProgress, self::Declined],
            self::InProgress => [self::Done],
            self::Done, self::Declined => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function acceptsVotes(): bool
    {
        return ! in_array($this, [self::Done, self::Declined], true);
    }

    public function isEditable(): bool
    {
        return $this === self::New;
    }
}
