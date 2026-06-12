<?php

declare(strict_types=1);

namespace App\Runtime;

final class ErrorDisplay
{
    public static function configureForBrowser(): void
    {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
    }
}
