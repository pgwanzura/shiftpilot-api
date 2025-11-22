<?php

namespace App\Exceptions;

use App\Enums\AssignmentStatus;
use Exception;

class AssignmentException extends Exception
{
    public static function overlappingAssignments(): self
    {
        return new self('Employee has overlapping assignments', 422);
    }

    public static function cannotUpdate(): self
    {
        return new self('Assignment cannot be updated in its current state', 422);
    }

    public static function invalidRates(): self
    {
        return new self('Agreed rate cannot be less than pay rate', 422);
    }

    public static function invalidStatusTransition(
        AssignmentStatus $from,
        AssignmentStatus $to
    ): self {
        return new self(
            "Cannot transition from {$from->value} to {$to->value}",
            422
        );
    }

    public static function cannotExtend(): self
    {
        return new self('Assignment cannot be extended', 422);
    }

    public static function invalidExtensionDate(): self
    {
        return new self('Extension date must be after current end date', 422);
    }

    public static function missingAgencyResponse(): self
    {
        return new self('Standard assignments require an agency response', 422);
    }

    public static function inactiveContract(): self
    {
        return new self('Assignment requires an active contract', 422);
    }

    public static function inactiveEmployee(): self
    {
        return new self('Assignment requires an active agency employee', 422);
    }
}
