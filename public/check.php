<?php

declare(strict_types=1);

use App\Database;
use App\Config\DatabaseConfig;
use App\Http\JsonResponder;
use App\TicketService;

require_once dirname(__DIR__) . '/vendor/autoload.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$token = filter_input(INPUT_GET, 'token', FILTER_UNSAFE_RAW);

if (! is_string($token)) {
    JsonResponder::send(
        [
            'status' => 'invalid',
            'message' => 'The token query parameter is required.',
            'ticket' => null,
        ],
        400,
    );
}

$config = DatabaseConfig::fromEnvironment();
$ticketService = new TicketService(
    new Database($config['uri'], $config['databaseName'], $config['collectionName']),
);
$result = $ticketService->checkInToken($token);

JsonResponder::send(
    [
        'status' => $result['status'],
        'message' => $result['message'],
        'ticket' => $result['ticket'],
    ],
    $result['httpStatus'],
);
