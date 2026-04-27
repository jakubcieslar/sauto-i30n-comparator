<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ListingRepository;
use App\Repository\ScrapeRunRepository;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ListingRepository $listings,
        private readonly ScrapeRunRepository $runs,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/', name: 'dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $now = $this->clock->now();
        $active = $this->listings->findAllActiveOrderedByPrice();

        $prices = array_filter(array_map(fn ($l) => $l->getCurrentPrice(), $active), fn ($p) => $p !== null);
        sort($prices);
        $count = count($prices);

        $stats = [
            'count' => count($active),
            'min' => $prices[0] ?? null,
            'max' => $prices[$count - 1] ?? null,
            'median' => $count > 0 ? (int) $prices[(int) floor(($count - 1) / 2)] : null,
            'average' => $count > 0 ? (int) round(array_sum($prices) / $count) : null,
        ];

        return $this->render('dashboard/index.html.twig', [
            'now' => $now,
            'active' => $active,
            'stats' => $stats,
            'lastRun' => $this->runs->findLatest(),
            'priceLabels' => array_map(fn ($l) => $l->getTitle(), $active),
            'priceData' => array_map(fn ($l) => $l->getCurrentPrice(), $active),
        ]);
    }
}
