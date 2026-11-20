<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\HttpClient\Message;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use Hyperplural\WarfaceSdk\Exception\ApiResponseErrorException;
use RuntimeException;

use function json_decode;

use const JSON_THROW_ON_ERROR;

final class ResponseMediator implements ResponseMediatorInterface
{
    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @return array<string|int, mixed>
     * @throws ApiResponseErrorException
     */
    public function getBodyContentsDecode(): array
    {
        $stream = $this->response->getBody();

        try {
            if ($stream->isSeekable()) {
                $stream->rewind();
            } elseif ($stream->tell() !== 0) {
                throw new ApiResponseErrorException(
                    'Warface API response body was already partially consumed',
                    $this->response->getStatusCode()
                );
            }

            $body = $stream->getContents();
        } catch (RuntimeException $exception) {
            throw new ApiResponseErrorException(
                'Failed to read Warface API response body',
                $this->response->getStatusCode(),
                $exception
            );
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ApiResponseErrorException(
                'Warface API returned invalid JSON',
                $this->response->getStatusCode(),
                $exception
            );
        }

        if (!is_array($decoded)) {
            throw new ApiResponseErrorException(
                'Warface API returned an unexpected JSON structure',
                $this->response->getStatusCode()
            );
        }

        return $decoded;
    }
}
