<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Exception;

final class RateLimitException extends ApiResponseErrorException
{
    public function __construct(string $message, int $statusCode, private readonly ?string $retryAfter)
    {
        parent::__construct($message, $statusCode);
    }

    /** Returns the raw Retry-After header value, if present. */
    public function getRetryAfter(): ?string
    {
        return $this->retryAfter;
    }
}
