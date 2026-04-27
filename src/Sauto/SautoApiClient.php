<?php

declare(strict_types=1);

namespace App\Sauto;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SautoApiClient
{
    private const PAGE_SIZE = 100;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(param: 'app.sauto.search_endpoint')]
        private readonly string $endpoint,
        #[Autowire(param: 'app.sauto.search_params')]
        private readonly array $searchParams,
    ) {
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    public function fetchAllListings(): iterable
    {
        $offset = 0;
        do {
            $payload = $this->fetchPage($offset, self::PAGE_SIZE);
            $results = $payload['results'] ?? [];
            foreach ($results as $result) {
                yield $result;
            }
            $total = $payload['pagination']['total'] ?? 0;
            $offset += count($results);
        } while ($offset < $total && count($results) > 0);
    }

    /**
     * @return array{pagination: array{limit: int, offset: int, total: int}, results: array<int, array<string, mixed>>}
     */
    public function fetchPage(int $offset, int $limit): array
    {
        $query = array_merge($this->searchParams, [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        $response = $this->httpClient->request('GET', $this->endpoint, [
            'query' => $query,
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Language' => 'cs',
            ],
            'timeout' => 30,
        ]);

        return $response->toArray();
    }
}
