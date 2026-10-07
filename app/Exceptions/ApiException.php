<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A business-rule failure the mobile app is expected to handle, identified by a stable error code.
 */
class ApiException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 422,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }
}
