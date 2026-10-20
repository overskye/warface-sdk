<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\HttpClient\Plugin;

use Fig\Http\Message\StatusCodeInterface;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Hyperplural\WarfaceSdk\Exception\ApiResponseErrorException;
use Hyperplural\WarfaceSdk\Exception\BadRequestException;
use Hyperplural\WarfaceSdk\Exception\InternalServerErrorException;
use Hyperplural\WarfaceSdk\Exception\NotFoundException;
use Hyperplural\WarfaceSdk\Exception\WarfaceApiException;
use JsonException;

use function is_array;
use function is_string;
use function json_decode;
use function str_contains;

use const JSON_THROW_ON_ERROR;

final class WarfaceClientExceptionPlugin implements Plugin
{
    /**
     * @throws WarfaceApiException
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        return $next($request)->then(static function (ResponseInterface $response): ResponseInterface {
            $status = $response->getStatusCode();
            switch ($status) {
                case StatusCodeInterface::STATUS_OK:
                    // Do nothing
                    break;
                case StatusCodeInterface::STATUS_BAD_REQUEST:
                    $message = null;
                    $bodyContents = $response->getBody()->__toString();

                    if (str_contains($response->getHeaderLine('Content-Type'), 'application/json')) {
                        try {
                            /** @var mixed $body */
                            $body = json_decode($bodyContents, true, 512, JSON_THROW_ON_ERROR);

                            if (is_array($body) && is_string($body['message'] ?? null)) {
                                $message = $body['message'];
                            }
                        } catch (JsonException) {
                            // Fall back to the raw response body for malformed JSON errors.
                        }
                    }

                    throw new BadRequestException($message ?? $bodyContents, $status);
                case StatusCodeInterface::STATUS_NOT_FOUND:
                    throw new NotFoundException('Not Found', $status);
                case StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR:
                    throw new InternalServerErrorException('Internal Server Error', $status);
                default:
                    throw new ApiResponseErrorException('Unknown error from API', $status);
            }

            return $response;
        });
    }
}
