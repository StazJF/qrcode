<?php

declare(strict_types=1);

use App\Database;
use App\Config\DatabaseConfig;
use App\Runtime\ErrorDisplay;
use App\TicketService;
use App\View\Html;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

require_once dirname(__DIR__) . '/src/Runtime/ErrorDisplay.php';

ErrorDisplay::configureForBrowser();

require_once dirname(__DIR__) . '/vendor/autoload.php';

$config = DatabaseConfig::fromEnvironment();
$ticketService = new TicketService(
    new Database($config['uri'], $config['databaseName'], $config['collectionName']),
);

$errors = [];
$ticketToken = null;
$qrCodeDataUri = null;
$name = '';
$email = '';

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'POST') {
    $submittedName = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $submittedEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $name = is_string($submittedName) ? $submittedName : '';
    $email = is_string($submittedEmail) ? $submittedEmail : '';

    try {
        $ticketToken = $ticketService->registerTicket($name, $email);
        $ticketUrl = 'https://yourdomain.com/ticket/' . $ticketToken;

        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->data($ticketUrl)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(320)
            ->margin(12)
            ->build();

        $qrCodeDataUri = $qrCode->getDataUri();
        $name = '';
        $email = '';
    } catch (Throwable $throwable) {
        $errors[] = $throwable->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event Ticket Registration</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            background: #f5f7fa;
            color: #1f2937;
        }

        main {
            width: min(720px, calc(100% - 32px));
            margin: 48px auto;
            padding: 24px;
            background: #ffffff;
            border: 1px solid #d8dee8;
            border-radius: 8px;
        }

        label,
        input,
        button {
            display: block;
            width: 100%;
        }

        label {
            margin-top: 16px;
            font-weight: 700;
        }

        input {
            box-sizing: border-box;
            margin-top: 6px;
            padding: 12px;
            border: 1px solid #aeb7c4;
            border-radius: 6px;
            font: inherit;
        }

        button {
            margin-top: 20px;
            padding: 12px 16px;
            border: 0;
            border-radius: 6px;
            background: #0f766e;
            color: #ffffff;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
        }

        .notice {
            margin-top: 20px;
            padding: 16px;
            border-radius: 6px;
        }

        .notice.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .notice.success {
            background: #dcfce7;
            color: #166534;
        }

        .qr-code {
            max-width: 320px;
            width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
<main>
    <h1>Register Ticket</h1>

    <?php if ($errors !== []) : ?>
        <section class="notice error" aria-live="polite">
            <?php foreach ($errors as $error) : ?>
                <p><?= Html::escape($error) ?></p>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($ticketToken !== null && $qrCodeDataUri !== null) : ?>
        <section class="notice success" aria-live="polite">
            <h2>Ticket Created</h2>
            <p>Token: <code><?= Html::escape($ticketToken) ?></code></p>
            <img class="qr-code" src="<?= Html::escape($qrCodeDataUri) ?>" alt="Ticket QR code">
        </section>
    <?php endif; ?>

    <form method="post" action="register.php">
        <label for="name">Name</label>
        <input
            id="name"
            name="name"
            type="text"
            maxlength="100"
            value="<?= Html::escape($name) ?>"
            autocomplete="name"
            required
        >

        <label for="email">Email</label>
        <input
            id="email"
            name="email"
            type="email"
            maxlength="100"
            value="<?= Html::escape($email) ?>"
            autocomplete="email"
            required
        >

        <button type="submit">Generate Ticket</button>
    </form>
</main>
</body>
</html>
