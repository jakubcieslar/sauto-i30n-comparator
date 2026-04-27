<?php

declare(strict_types=1);

namespace App\Matching;

enum MatchType: string
{
    case OwnerCustomId = 'owner_custom_id';
    case Exact = 'exact';
    case PartialSameOwner = 'partial_same_owner';
    case PartialDifferentOwner = 'partial_different_owner';

    public function label(): string
    {
        return match ($this) {
            self::OwnerCustomId => 'podle ID u prodejce',
            self::Exact => 'shoda parametrů',
            self::PartialSameOwner => 'částečná shoda — stejný prodejce',
            self::PartialDifferentOwner => 'částečná shoda — jiný prodejce',
        };
    }
}
