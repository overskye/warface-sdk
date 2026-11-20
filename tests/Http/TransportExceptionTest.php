<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Tests\Http;

use Hyperplural\WarfaceSdk\Client;
use Hyperplural\WarfaceSdk\Exception\ApiResponseErrorException;
use Hyperplural\WarfaceSdk\Exception\TransportException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class TransportExceptionTest extends TestCase
{
    public function testRetainsOriginalTransportExceptionWithoutUsingItsCodeAsStatus(): void
    {
        $cause = new class ('Connection failed', 123) extends \RuntimeException implements ClientExceptionInterface {
        };
        $http = new class ($cause) implements ClientInterface {
            public function __construct(private readonly ClientExceptionInterface $cause)
            {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw $this->cause;
            }
        };

        try {
            Client::createWithHttpClient($http)->game()->missions();
            self::fail('Expected a transport error');
        } catch (TransportException $exception) {
            self::assertInstanceOf(ApiResponseErrorException::class, $exception);
            self::assertSame($cause, $exception->getPrevious());
            self::assertSame(0, $exception->getCode());
        }
    }
}
