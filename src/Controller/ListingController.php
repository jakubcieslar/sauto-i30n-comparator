<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Listing;
use App\Repository\ListingRepository;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class ListingController extends AbstractController
{
    public function __construct(
        private readonly ListingRepository $listings,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/inzerat/{externalId}', name: 'listing_show', requirements: ['externalId' => '\d+'], methods: ['GET'])]
    public function show(int $externalId): Response
    {
        $listing = $this->listings->findByExternalId($externalId);
        if ($listing === null) {
            throw new NotFoundHttpException(sprintf('Listing %d not found', $externalId));
        }

        $snapshots = $listing->getPriceSnapshots()->toArray();

        return $this->render('listing/show.html.twig', [
            'listing' => $listing,
            'now' => $this->clock->now(),
            'daysListed' => $listing->getDaysListed($this->clock->now()),
            'priceHistory' => array_map(
                fn ($s) => ['t' => $s->getObservedAt()->format('c'), 'price' => $s->getPrice()],
                $snapshots,
            ),
        ]);
    }
}
