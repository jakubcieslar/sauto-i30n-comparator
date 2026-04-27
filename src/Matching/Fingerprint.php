<?php

declare(strict_types=1);

namespace App\Matching;

use App\Entity\Listing;

final class Fingerprint
{
    public const FIELDS = [
        'additionalModelName',
        'manufacturingDate',
        'inOperationDate',
        'gearbox',
        'fuel',
        'district',
    ];

    private const NULL_TOKEN = '?';
    private const SEPARATOR = '|';

    /**
     * @param array<string, string|null> $values keyed by FIELDS
     */
    public function __construct(private readonly array $values)
    {
    }

    public static function fromListing(Listing $listing): self
    {
        return new self([
            'additionalModelName' => self::normalize($listing->getAdditionalModelName()),
            'manufacturingDate' => $listing->getManufacturingDate()?->format('Y-m-d'),
            'inOperationDate' => $listing->getInOperationDate()?->format('Y-m-d'),
            'gearbox' => self::normalize($listing->getGearbox()),
            'fuel' => self::normalize($listing->getFuel()),
            'district' => self::normalize($listing->getLocationDistrict()),
        ]);
    }

    public static function fromString(string $s): self
    {
        $parts = explode(self::SEPARATOR, $s);
        if (count($parts) !== count(self::FIELDS)) {
            throw new \InvalidArgumentException(sprintf(
                'Fingerprint string must have %d parts, got %d in "%s"',
                count(self::FIELDS),
                count($parts),
                $s,
            ));
        }

        $values = [];
        foreach (self::FIELDS as $i => $field) {
            $values[$field] = $parts[$i] === self::NULL_TOKEN ? null : $parts[$i];
        }

        return new self($values);
    }

    public function toString(): string
    {
        $parts = [];
        foreach (self::FIELDS as $field) {
            $parts[] = $this->values[$field] ?? self::NULL_TOKEN;
        }

        return implode(self::SEPARATOR, $parts);
    }

    /**
     * @return array<string, string|null>
     */
    public function values(): array
    {
        return $this->values;
    }

    /**
     * Names of fields where the two fingerprints differ.
     * Two nulls are considered equal.
     *
     * @return list<string>
     */
    public function diff(self $other): array
    {
        $mismatches = [];
        foreach (self::FIELDS as $field) {
            if ($this->values[$field] !== $other->values[$field]) {
                $mismatches[] = $field;
            }
        }

        return $mismatches;
    }

    /**
     * True only when all 6 fields have a non-null value.
     * Incomplete fingerprints are weak — matcher should treat them carefully.
     */
    public function isComplete(): bool
    {
        foreach ($this->values as $v) {
            if ($v === null) {
                return false;
            }
        }

        return true;
    }

    private static function normalize(?string $s): ?string
    {
        if ($s === null) {
            return null;
        }
        $trimmed = trim($s);

        return $trimmed === '' ? null : $trimmed;
    }
}
