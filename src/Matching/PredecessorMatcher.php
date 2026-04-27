<?php

declare(strict_types=1);

namespace App\Matching;

use App\Entity\Listing;
use App\Repository\ListingRepository;

class PredecessorMatcher
{
    public const LOOKBACK_DAYS = 90;
    public const MILEAGE_DECREASE_TOLERANCE_KM = 2000;
    public const MAX_MISMATCHES_FOR_PARTIAL = 1;

    public function __construct(private readonly ListingRepository $listings)
    {
    }

    public function findPredecessor(
        Fingerprint $fingerprint,
        OwnerKey $ownerKey,
        ?int $currentMileageKm,
        \DateTimeImmutable $now,
    ): ?PredecessorMatch {
        $since = $now->modify(sprintf('-%d days', self::LOOKBACK_DAYS));
        $candidates = $this->listings->findRemovedSince($since);

        $best = null;
        foreach ($candidates as $candidate) {
            $match = $this->scoreCandidate($candidate, $fingerprint, $ownerKey, $currentMileageKm);
            if ($match === null) {
                continue;
            }
            if ($best === null || $this->isBetter($match, $best)) {
                $best = $match;
            }
        }

        return $best;
    }

    private function scoreCandidate(
        Listing $candidate,
        Fingerprint $fingerprint,
        OwnerKey $ownerKey,
        ?int $currentMileageKm,
    ): ?PredecessorMatch {
        $candidateFingerprintStr = $candidate->getFingerprint();
        $candidateOwnerKeyStr = $candidate->getOwnerKey();
        if ($candidateFingerprintStr === null || $candidateOwnerKeyStr === null) {
            return null;
        }

        if (!$this->mileageOk($candidate->getMileageKm(), $currentMileageKm)) {
            return null;
        }

        if ($ownerKey->isDeterministic() && $ownerKey->toString() === $candidateOwnerKeyStr) {
            return new PredecessorMatch($candidate, MatchType::OwnerCustomId, []);
        }

        $candidateFingerprint = Fingerprint::fromString($candidateFingerprintStr);
        $mismatches = $fingerprint->diff($candidateFingerprint);
        $mismatchCount = count($mismatches);

        if ($mismatchCount === 0 && $fingerprint->isComplete() && $candidateFingerprint->isComplete()) {
            return new PredecessorMatch($candidate, MatchType::Exact, []);
        }

        if ($mismatchCount > self::MAX_MISMATCHES_FOR_PARTIAL) {
            return null;
        }

        if (!$fingerprint->isComplete() || !$candidateFingerprint->isComplete()) {
            return null;
        }

        $candidateOwnerKey = $this->parseOwnerKey($candidateOwnerKeyStr, $candidate);
        $sameOwner = $candidateOwnerKey !== null && $ownerKey->sameOwnerAs($candidateOwnerKey);

        return new PredecessorMatch(
            $candidate,
            $sameOwner ? MatchType::PartialSameOwner : MatchType::PartialDifferentOwner,
            $mismatches,
        );
    }

    private function mileageOk(?int $previous, ?int $current): bool
    {
        if ($previous === null || $current === null) {
            return true;
        }

        return $current >= $previous - self::MILEAGE_DECREASE_TOLERANCE_KM;
    }

    private function parseOwnerKey(string $stored, Listing $listing): ?OwnerKey
    {
        $rawData = $listing->getRawData();
        if ($rawData === []) {
            return null;
        }

        return OwnerKey::fromRawData($rawData);
    }

    /**
     * Ranking: deterministic > exact > partial-same-owner > partial-different-owner;
     * within partial, fewer mismatches wins; finally, more recent removedAt wins.
     */
    private function isBetter(PredecessorMatch $a, PredecessorMatch $b): bool
    {
        $rank = [
            MatchType::OwnerCustomId->value => 4,
            MatchType::Exact->value => 3,
            MatchType::PartialSameOwner->value => 2,
            MatchType::PartialDifferentOwner->value => 1,
        ];
        $ra = $rank[$a->type->value];
        $rb = $rank[$b->type->value];
        if ($ra !== $rb) {
            return $ra > $rb;
        }
        if (count($a->mismatchedFields) !== count($b->mismatchedFields)) {
            return count($a->mismatchedFields) < count($b->mismatchedFields);
        }
        $aRemoved = $a->predecessor->getRemovedAt();
        $bRemoved = $b->predecessor->getRemovedAt();
        if ($aRemoved !== null && $bRemoved !== null) {
            return $aRemoved > $bRemoved;
        }

        return false;
    }
}
