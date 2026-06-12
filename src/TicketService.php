<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Operation\FindOneAndUpdate;

/**
 * @phpstan-type TicketDocument array{
 *     _id: ObjectId,
 *     userName: string,
 *     userEmail: string,
 *     ticketToken: string,
 *     isCheckedIn: bool,
 *     checkedInAt: UTCDateTime|null,
 *     createdAt: UTCDateTime
 * }
 * @phpstan-type CheckInResult array{
 *     status: 'checked_in'|'already_checked_in'|'invalid'|'not_found',
 *     httpStatus: 200|400|404|409,
 *     message: string,
 *     ticket: array{
 *         id: string,
 *         userName: string,
 *         userEmail: string,
 *         checkedInAt: string|null
 *     }|null
 * }
 */
final class TicketService
{
    public function __construct(
        private readonly Database $database,
    ) {
    }

    public function registerTicket(string $name, string $email): string
    {
        $normalizedName = trim($name);
        $normalizedEmail = trim($email);

        if ($normalizedName === '' || mb_strlen($normalizedName) > 100) {
            throw new InvalidArgumentException('Name must be between 1 and 100 characters.');
        }

        if (
            $normalizedEmail === ''
            || mb_strlen($normalizedEmail) > 100
            || filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) === false
        ) {
            throw new InvalidArgumentException('A valid email address is required.');
        }

        $token = bin2hex(random_bytes(32));

        $this->database->tickets()->insertOne([
            'userName' => $normalizedName,
            'userEmail' => $normalizedEmail,
            'ticketToken' => $token,
            'isCheckedIn' => false,
            'checkedInAt' => null,
            'createdAt' => new UTCDateTime(),
        ]);

        return $token;
    }

    /**
     * @return CheckInResult
     */
    public function checkInToken(string $token): array
    {
        $normalizedToken = strtolower(trim($token));

        if (preg_match('/\A[a-f0-9]{64}\z/', $normalizedToken) !== 1) {
            return $this->buildResult('invalid', null, null);
        }

        $checkedInAt = new UTCDateTime();
        /** @var TicketDocument|null $updatedTicket */
        $updatedTicket = $this->database->tickets()->findOneAndUpdate(
            [
                'ticketToken' => $normalizedToken,
                'isCheckedIn' => false,
            ],
            [
                '$set' => [
                    'isCheckedIn' => true,
                    'checkedInAt' => $checkedInAt,
                ],
            ],
            [
                'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
            ],
        );

        if ($updatedTicket !== null) {
            return $this->buildResult('checked_in', $updatedTicket, $checkedInAt);
        }

        /** @var TicketDocument|null $existingTicket */
        $existingTicket = $this->database->tickets()->findOne([
            'ticketToken' => $normalizedToken,
        ]);

        if ($existingTicket === null) {
            return $this->buildResult('not_found', null, null);
        }

        return $this->buildResult('already_checked_in', $existingTicket, $existingTicket['checkedInAt']);
    }

    /**
     * @param TicketDocument|null $ticket
     * @param 'checked_in'|'already_checked_in'|'invalid'|'not_found' $status
     *
     * @return CheckInResult
     */
    private function buildResult(string $status, ?array $ticket, ?UTCDateTime $checkedInAt): array
    {
        $message = match ($status) {
            'checked_in' => 'Ticket checked in successfully.',
            'already_checked_in' => 'Ticket has already been checked in.',
            'invalid' => 'Invalid ticket token format.',
            'not_found' => 'Ticket token was not found.',
        };

        $httpStatus = match ($status) {
            'checked_in' => 200,
            'already_checked_in' => 409,
            'invalid' => 400,
            'not_found' => 404,
        };

        return [
            'status' => $status,
            'httpStatus' => $httpStatus,
            'message' => $message,
            'ticket' => $ticket === null ? null : [
                'id' => (string) $ticket['_id'],
                'userName' => $ticket['userName'],
                'userEmail' => $ticket['userEmail'],
                'checkedInAt' => $checkedInAt?->toDateTime()->format('Y-m-d H:i:s'),
            ],
        ];
    }
}
