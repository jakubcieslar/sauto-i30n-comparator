<?php

declare(strict_types=1);

namespace App\Command;

use App\Sauto\ListingSynchronizer;
use App\Sauto\SautoApiClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:scrape', description: 'Fetch sauto listings and persist diffs / price snapshots')]
class ScrapeCommand extends Command
{
    public function __construct(
        private readonly SautoApiClient $api,
        private readonly ListingSynchronizer $synchronizer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Sauto scrape');

        $run = $this->synchronizer->sync($this->api->fetchAllListings());

        $io->definitionList(
            ['Started' => $run->getStartedAt()->format('Y-m-d H:i:s')],
            ['Finished' => $run->getFinishedAt()?->format('Y-m-d H:i:s') ?? '-'],
            ['Listings seen' => (string) $run->getListingsSeen()],
            ['Newly added' => (string) $run->getListingsAdded()],
            ['Price changed' => (string) $run->getListingsPriceChanged()],
            ['Marked removed' => (string) $run->getListingsMarkedRemoved()],
        );

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
