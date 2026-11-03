<?php

declare(strict_types=1);

namespace App\Enums;

enum FeatureRequestSort: string
{
    case Votes = 'votes';
    case Newest = 'newest';

    public function label(): string
    {
        return match ($this) {
            self::Votes => 'Meiste Stimmen',
            self::Newest => 'Neueste zuerst',
        };
    }

    public function column(): string
    {
        return match ($this) {
            self::Votes => 'votes_count',
            self::Newest => 'created_at',
        };
    }
}
