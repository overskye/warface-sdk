<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Exception;

use Exception;
use Throwable;

class WarfaceApiException extends Exception
{
    public function __construct(string $message, int $statusCode = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}
