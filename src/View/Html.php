<?php

declare(strict_types=1);

namespace App\View;

final class Html
{
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
