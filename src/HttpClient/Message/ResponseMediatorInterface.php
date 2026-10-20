<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\HttpClient\Message;

use Psr\Http\Message\ResponseInterface;
use Hyperplural\WarfaceSdk\Exception\ApiResponseErrorException;

interface ResponseMediatorInterface
{
    public function getResponse(): ResponseInterface;

    /**
     * @return array<string|int, mixed>
     * @throws ApiResponseErrorException
     */
    public function getBodyContentsDecode(): array;
}
