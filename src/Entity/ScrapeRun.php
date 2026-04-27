<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ScrapeRunRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScrapeRunRepository::class)]
#[ORM\Table(name: 'scrape_run')]
class ScrapeRun
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column]
    private int $listingsSeen = 0;

    #[ORM\Column]
    private int $listingsAdded = 0;

    #[ORM\Column]
    private int $listingsPriceChanged = 0;

    #[ORM\Column]
    private int $listingsMarkedRemoved = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorText = null;

    public function __construct(\DateTimeImmutable $startedAt)
    {
        $this->startedAt = $startedAt;
    }

    public function finish(\DateTimeImmutable $finishedAt, int $seen, int $added, int $priceChanged, int $markedRemoved): void
    {
        $this->finishedAt = $finishedAt;
        $this->listingsSeen = $seen;
        $this->listingsAdded = $added;
        $this->listingsPriceChanged = $priceChanged;
        $this->listingsMarkedRemoved = $markedRemoved;
    }

    public function fail(\DateTimeImmutable $failedAt, string $error): void
    {
        $this->finishedAt = $failedAt;
        $this->errorText = $error;
    }

    public function getId(): ?int { return $this->id; }
    public function getStartedAt(): \DateTimeImmutable { return $this->startedAt; }
    public function getFinishedAt(): ?\DateTimeImmutable { return $this->finishedAt; }
    public function getListingsSeen(): int { return $this->listingsSeen; }
    public function getListingsAdded(): int { return $this->listingsAdded; }
    public function getListingsPriceChanged(): int { return $this->listingsPriceChanged; }
    public function getListingsMarkedRemoved(): int { return $this->listingsMarkedRemoved; }
    public function getErrorText(): ?string { return $this->errorText; }
}
