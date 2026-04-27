<?php

declare(strict_types=1);

namespace App\Sauto;

use App\Entity\Listing;
use App\Entity\PriceSnapshot;
use App\Entity\ScrapeRun;
use App\Matching\Fingerprint;
use App\Matching\OwnerKey;
use App\Matching\PredecessorMatcher;
use App\Repository\ListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ListingSynchronizer
{
    public function __construct(
        private readonly ListingRepository $listings,
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $clock,
        private readonly PredecessorMatcher $predecessorMatcher,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @param iterable<array<string, mixed>> $apiResults
     */
    public function sync(iterable $apiResults): ScrapeRun
    {
        $now = $this->clock->now();
        $run = new ScrapeRun($now);
        $this->em->persist($run);
        $this->em->flush();

        $seen = 0;
        $added = 0;
        $priceChanged = 0;
        $seenExternalIds = [];

        try {
            foreach ($apiResults as $result) {
                $externalId = (int) $result['id'];
                $seenExternalIds[] = $externalId;
                $seen++;

                $existing = $this->listings->findByExternalId($externalId);

                if ($existing === null) {
                    $listing = $this->createListing($result, $now);
                    $this->linkPredecessor($listing, $now);
                    $this->em->persist($listing);
                    $added++;

                    if ($listing->getCurrentPrice() !== null) {
                        $this->em->persist(new PriceSnapshot($listing, $listing->getCurrentPrice(), $now));
                    }
                } else {
                    $previousPrice = $existing->getCurrentPrice();
                    $this->updateListing($existing, $result, $now);

                    if ($existing->getCurrentPrice() !== null) {
                        $this->em->persist(new PriceSnapshot($existing, $existing->getCurrentPrice(), $now));
                        if ($existing->getCurrentPrice() !== $previousPrice) {
                            $priceChanged++;
                        }
                    }
                }
            }

            $disappeared = $this->listings->findActiveExcept($seenExternalIds);
            $markedRemoved = 0;
            foreach ($disappeared as $listing) {
                $listing->setRemovedAt($now);
                $markedRemoved++;
            }

            $run->finish($this->clock->now(), $seen, $added, $priceChanged, $markedRemoved);
            $this->em->flush();

            $this->logger->info('Sauto sync done', [
                'seen' => $seen,
                'added' => $added,
                'price_changed' => $priceChanged,
                'marked_removed' => $markedRemoved,
            ]);
        } catch (\Throwable $e) {
            $this->em->clear();
            $run = $this->em->find(ScrapeRun::class, $run->getId()) ?? $run;
            $run->fail($this->clock->now(), $e->getMessage());
            $this->em->persist($run);
            $this->em->flush();
            throw $e;
        }

        return $run;
    }

    private function createListing(array $r, \DateTimeImmutable $now): Listing
    {
        $listing = new Listing(
            externalId: (int) $r['id'],
            title: (string) ($r['name'] ?? '(bez názvu)'),
            modelSeo: (string) ($r['model_cb']['seo_name'] ?? 'unknown'),
            modelName: (string) ($r['model_cb']['name'] ?? 'unknown'),
            now: $now,
        );

        $this->applyPayload($listing, $r, $now);

        return $listing;
    }

    private function updateListing(Listing $listing, array $r, \DateTimeImmutable $now): void
    {
        $listing->setTitle((string) ($r['name'] ?? $listing->getTitle()));
        $this->applyPayload($listing, $r, $now);
        $listing->setLastSeenAt($now);
        $listing->setRemovedAt(null);
    }

    private function applyPayload(Listing $listing, array $r, \DateTimeImmutable $now): void
    {
        $price = $r['price'] ?? null;
        $listing->setCurrentPrice(is_int($price) ? $price : null);
        $listing->setPriceByAgreement((bool) ($r['price_by_agreement'] ?? false));

        $listing->setMileageKm(isset($r['tachometer']) ? (int) $r['tachometer'] : null);
        $listing->setManufacturingDate($this->parseDate($r['manufacturing_date'] ?? null));
        $listing->setInOperationDate($this->parseDate($r['in_operation_date'] ?? null));

        $listing->setAdditionalModelName($r['additional_model_name'] ?? null);
        $listing->setFuel($r['fuel_cb']['name'] ?? null);
        $listing->setGearbox($r['gearbox_cb']['name'] ?? null);
        $listing->setLocationDistrict($r['locality']['district'] ?? null);
        $listing->setLocationMunicipality($r['locality']['municipality'] ?? null);

        $listing->setListedAt($this->parseDateTime($r['create_date'] ?? null));
        $listing->setLastEditedAt($this->parseDateTime($r['edit_date'] ?? null));
        $listing->setLastSeenAt($now);

        $manufacturerSeo = (string) ($r['manufacturer_cb']['seo_name'] ?? '');
        $modelSeo = (string) ($r['model_cb']['seo_name'] ?? '');
        if ($manufacturerSeo !== '' && $modelSeo !== '' && isset($r['id'])) {
            $listing->setDetailUrl(sprintf(
                'https://www.sauto.cz/osobni-auta/detail/%s/%s/%d',
                $manufacturerSeo,
                $modelSeo,
                (int) $r['id']
            ));
        }

        $listing->setRawData($r);
        $listing->setFingerprint(Fingerprint::fromListing($listing)->toString());
        $listing->setOwnerKey(OwnerKey::fromRawData($r)->toString());
    }

    private function linkPredecessor(Listing $listing, \DateTimeImmutable $now): void
    {
        $rawData = $listing->getRawData();
        $ownerKey = OwnerKey::fromRawData($rawData);
        $fingerprint = Fingerprint::fromListing($listing);

        $match = $this->predecessorMatcher->findPredecessor(
            $fingerprint,
            $ownerKey,
            $listing->getMileageKm(),
            $now,
        );

        if ($match === null) {
            return;
        }

        $listing->setPredecessor($match->predecessor, $match->type);
        $this->logger->info('Predecessor linked', [
            'external_id' => $listing->getExternalId(),
            'predecessor_external_id' => $match->predecessor->getExternalId(),
            'match_type' => $match->type->value,
            'mismatched_fields' => $match->mismatchedFields,
        ]);
    }

    private function parseDate(?string $s): ?\DateTimeImmutable
    {
        if ($s === null || $s === '') {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('!Y-m-d', substr($s, 0, 10)) ?: null;
    }

    private function parseDateTime(?string $s): ?\DateTimeImmutable
    {
        if ($s === null || $s === '') {
            return null;
        }
        try {
            return new \DateTimeImmutable($s);
        } catch (\Exception) {
            return null;
        }
    }
}
