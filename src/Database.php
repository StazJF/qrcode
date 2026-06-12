<?php

declare(strict_types=1);

namespace App;

use MongoDB\Client;
use MongoDB\Collection;

final class Database
{
    private Client $client;

    /**
     * @param array<string, mixed> $uriOptions MongoDB URI options.
     * @param array<string, mixed> $driverOptions MongoDB driver options.
     */
    public function __construct(
        private readonly string $uri,
        private readonly string $databaseName,
        private readonly string $collectionName,
        private readonly array $uriOptions = [],
        private readonly array $driverOptions = [],
    ) {
        $this->client = new Client(
            $this->uri,
            $this->uriOptions,
            $this->driverOptions,
        );
    }

    /**
     * @return Collection
     */
    public function tickets(): Collection
    {
        return $this->client->selectCollection(
            $this->databaseName,
            $this->collectionName,
            [
                'typeMap' => [
                    'array' => 'array',
                    'document' => 'array',
                    'root' => 'array',
                ],
            ],
        );
    }
}
