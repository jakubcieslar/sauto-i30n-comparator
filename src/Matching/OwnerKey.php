<?php

declare(strict_types=1);

namespace App\Matching;

final class OwnerKey
{
    public const KIND_PREMISE_CUSTOM = 'premise_custom';
    public const KIND_PREMISE = 'premise';
    public const KIND_USER = 'user';
    public const KIND_NONE = 'none';

    private function __construct(
        public readonly string $kind,
        public readonly string $value,
    ) {
    }

    /**
     * @param array<string, mixed> $rawData
     */
    public static function fromRawData(array $rawData): self
    {
        $premiseId = self::idOrNull($rawData['premise'] ?? null);
        $customId = self::stringOrNull($rawData['custom_id'] ?? null);

        if ($premiseId !== null && $customId !== null) {
            return new self(self::KIND_PREMISE_CUSTOM, sprintf('premise:%d:%s', $premiseId, $customId));
        }
        if ($premiseId !== null) {
            return new self(self::KIND_PREMISE, sprintf('premise:%d', $premiseId));
        }

        $userId = self::idOrNull($rawData['user'] ?? null);
        if ($userId !== null) {
            return new self(self::KIND_USER, sprintf('user:%d', $userId));
        }

        return new self(self::KIND_NONE, 'none');
    }

    public function isDeterministic(): bool
    {
        return $this->kind === self::KIND_PREMISE_CUSTOM;
    }

    public function premiseOrUserPart(): ?string
    {
        return match ($this->kind) {
            self::KIND_PREMISE_CUSTOM, self::KIND_PREMISE => explode(':', $this->value, 3)[1],
            self::KIND_USER => explode(':', $this->value, 2)[1],
            default => null,
        };
    }

    /**
     * Same person/dealer? `premise:X:custom_A` and `premise:X:custom_B` count as same owner.
     */
    public function sameOwnerAs(self $other): bool
    {
        if ($this->kind === self::KIND_NONE || $other->kind === self::KIND_NONE) {
            return false;
        }
        $a = $this->premiseOrUserPart();
        $b = $other->premiseOrUserPart();
        if ($a === null || $b === null) {
            return false;
        }

        $aIsPremise = in_array($this->kind, [self::KIND_PREMISE_CUSTOM, self::KIND_PREMISE], true);
        $bIsPremise = in_array($other->kind, [self::KIND_PREMISE_CUSTOM, self::KIND_PREMISE], true);

        return $aIsPremise === $bIsPremise && $a === $b;
    }

    public function toString(): string
    {
        return $this->value;
    }

    private static function idOrNull(mixed $node): ?int
    {
        if (!is_array($node)) {
            return null;
        }
        $id = $node['id'] ?? null;

        return is_int($id) || (is_string($id) && ctype_digit($id)) ? (int) $id : null;
    }

    private static function stringOrNull(mixed $v): ?string
    {
        if (!is_string($v)) {
            return null;
        }
        $t = trim($v);

        return $t === '' ? null : $t;
    }
}
