<?php

declare(strict_types=1);

namespace App\Http;

final class JsonResponder
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function send(array $payload, int $statusCode): never
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
