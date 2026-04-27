<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PriceSnapshotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PriceSnapshotRepository::class)]
#[ORM\Table(name: 'price_snapshot')]
#[ORM\Index(name: 'idx_price_snapshot_listing_observed', columns: ['listing_id', 'observed_at'])]
class PriceSnapshot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Listing::class, inversedBy: 'priceSnapshots')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Listing $listing;

    #[ORM\Column]
    private int $price;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $observedAt;

    public function __construct(Listing $listing, int $price, \DateTimeImmutable $observedAt)
    {
        $this->listing = $listing;
        $this->price = $price;
        $this->observedAt = $observedAt;
        $listing->addPriceSnapshot($this);
    }

    public function getId(): ?int { return $this->id; }
    public function getListing(): Listing { return $this->listing; }
    public function getPrice(): int { return $this->price; }
    public function getObservedAt(): \DateTimeImmutable { return $this->observedAt; }
}
