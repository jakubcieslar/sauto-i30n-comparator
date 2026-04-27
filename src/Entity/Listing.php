<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ListingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ListingRepository::class)]
#[ORM\Table(name: 'listing')]
#[ORM\Index(name: 'idx_listing_active', columns: ['removed_at'])]
class Listing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private int $externalId;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $detailUrl = null;

    #[ORM\Column(nullable: true)]
    private ?int $currentPrice = null;

    #[ORM\Column]
    private bool $priceByAgreement = false;

    #[ORM\Column(nullable: true)]
    private ?int $mileageKm = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $manufacturingDate = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $inOperationDate = null;

    #[ORM\Column(length: 50)]
    private string $modelSeo;

    #[ORM\Column(length: 50)]
    private string $modelName;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $additionalModelName = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $fuel = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $gearbox = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $locationDistrict = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $locationMunicipality = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $listedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastEditedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $firstSeenAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastSeenAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $removedAt = null;

    #[ORM\Column(type: 'json')]
    private array $rawData = [];

    /** @var Collection<int, PriceSnapshot> */
    #[ORM\OneToMany(targetEntity: PriceSnapshot::class, mappedBy: 'listing', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['observedAt' => 'ASC'])]
    private Collection $priceSnapshots;

    public function __construct(int $externalId, string $title, string $modelSeo, string $modelName, \DateTimeImmutable $now)
    {
        $this->externalId = $externalId;
        $this->title = $title;
        $this->modelSeo = $modelSeo;
        $this->modelName = $modelName;
        $this->firstSeenAt = $now;
        $this->lastSeenAt = $now;
        $this->priceSnapshots = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getExternalId(): int { return $this->externalId; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }

    public function getDetailUrl(): ?string { return $this->detailUrl; }
    public function setDetailUrl(?string $detailUrl): void { $this->detailUrl = $detailUrl; }

    public function getCurrentPrice(): ?int { return $this->currentPrice; }
    public function setCurrentPrice(?int $price): void { $this->currentPrice = $price; }

    public function isPriceByAgreement(): bool { return $this->priceByAgreement; }
    public function setPriceByAgreement(bool $v): void { $this->priceByAgreement = $v; }

    public function getMileageKm(): ?int { return $this->mileageKm; }
    public function setMileageKm(?int $v): void { $this->mileageKm = $v; }

    public function getManufacturingDate(): ?\DateTimeImmutable { return $this->manufacturingDate; }
    public function setManufacturingDate(?\DateTimeImmutable $v): void { $this->manufacturingDate = $v; }

    public function getInOperationDate(): ?\DateTimeImmutable { return $this->inOperationDate; }
    public function setInOperationDate(?\DateTimeImmutable $v): void { $this->inOperationDate = $v; }

    public function getModelSeo(): string { return $this->modelSeo; }
    public function getModelName(): string { return $this->modelName; }

    public function getAdditionalModelName(): ?string { return $this->additionalModelName; }
    public function setAdditionalModelName(?string $v): void { $this->additionalModelName = $v; }

    public function getFuel(): ?string { return $this->fuel; }
    public function setFuel(?string $v): void { $this->fuel = $v; }

    public function getGearbox(): ?string { return $this->gearbox; }
    public function setGearbox(?string $v): void { $this->gearbox = $v; }

    public function getLocationDistrict(): ?string { return $this->locationDistrict; }
    public function setLocationDistrict(?string $v): void { $this->locationDistrict = $v; }

    public function getLocationMunicipality(): ?string { return $this->locationMunicipality; }
    public function setLocationMunicipality(?string $v): void { $this->locationMunicipality = $v; }

    public function getListedAt(): ?\DateTimeImmutable { return $this->listedAt; }
    public function setListedAt(?\DateTimeImmutable $v): void { $this->listedAt = $v; }

    public function getLastEditedAt(): ?\DateTimeImmutable { return $this->lastEditedAt; }
    public function setLastEditedAt(?\DateTimeImmutable $v): void { $this->lastEditedAt = $v; }

    public function getFirstSeenAt(): \DateTimeImmutable { return $this->firstSeenAt; }
    public function getLastSeenAt(): \DateTimeImmutable { return $this->lastSeenAt; }
    public function setLastSeenAt(\DateTimeImmutable $v): void { $this->lastSeenAt = $v; }

    public function getRemovedAt(): ?\DateTimeImmutable { return $this->removedAt; }
    public function setRemovedAt(?\DateTimeImmutable $v): void { $this->removedAt = $v; }

    public function getRawData(): array { return $this->rawData; }
    public function setRawData(array $v): void { $this->rawData = $v; }

    /** @return Collection<int, PriceSnapshot> */
    public function getPriceSnapshots(): Collection { return $this->priceSnapshots; }

    public function addPriceSnapshot(PriceSnapshot $snapshot): void
    {
        if (!$this->priceSnapshots->contains($snapshot)) {
            $this->priceSnapshots->add($snapshot);
        }
    }

    public function isActive(): bool
    {
        return $this->removedAt === null;
    }

    public function getDaysListed(\DateTimeImmutable $now): ?int
    {
        if ($this->listedAt === null) {
            return null;
        }
        $end = $this->removedAt ?? $now;

        return (int) $this->listedAt->diff($end)->days;
    }
}
