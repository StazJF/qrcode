<?php

declare(strict_types=1);

namespace App\Config;

final class DatabaseConfig
{
    /**
     * @return array{uri: string, databaseName: string, collectionName: string}
     */
    public static function fromEnvironment(): array
    {
        return [
            'uri' => getenv('MONGODB_URI') !== false ? (string) getenv('MONGODB_URI') : 'mongodb://127.0.0.1:27017',
            'databaseName' => getenv('MONGODB_DATABASE') !== false
                ? (string) getenv('MONGODB_DATABASE')
                : 'event_ticketing',
            'collectionName' => getenv('MONGODB_COLLECTION') !== false
                ? (string) getenv('MONGODB_COLLECTION')
                : 'tickets',
        ];
    }
}
