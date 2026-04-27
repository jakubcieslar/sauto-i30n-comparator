<?php

declare(strict_types=1);

namespace App\Matching;

use App\Entity\Listing;

final class PredecessorMatch
{
    /**
     * @param list<string> $mismatchedFields
     */
    public function __construct(
        public readonly Listing $predecessor,
        public readonly MatchType $type,
        public readonly array $mismatchedFields,
    ) {
    }
}
