<?php

namespace App\Exceptions;

use RuntimeException;

class QrScanException extends RuntimeException
{
    public const NOT_FOUND = 'QR_NOT_FOUND';
    public const ALREADY_USED = 'QR_ALREADY_USED';
    public const EXPIRED = 'QR_EXPIRED';

    public function __construct(
        public readonly string $reason,
        string $message,
    ) {
        parent::__construct($message);
    }
}
