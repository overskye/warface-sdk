<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Tests\Plugin;

use GuzzleHttp\Psr7\Response;
use Hyperplural\WarfaceSdk\Client;
use Hyperplural\WarfaceSdk\Exception\ApiResponseErrorException;
use Hyperplural\WarfaceSdk\Exception\BadRequestException;
use Hyperplural\WarfaceSdk\Exception\InternalServerErrorException;
use Hyperplural\WarfaceSdk\Exception\NotFoundException;
use Hyperplural\WarfaceSdk\Exception\RateLimitException;
use Hyperplural\WarfaceSdk\Exception\ServerException;
use Hyperplural\WarfaceSdk\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class WarfaceClientExceptionPluginTest extends TestCase
{
    /** @return iterable<string, array{int, class-string<\Throwable>}> */
    public static function errorStatuses(): iterable
    {
        yield 'bad request' => [400, BadRequestException::class];
        yield 'not found' => [404, NotFoundException::class];
        yield 'rate limit' => [429, RateLimitException::class];
        yield 'internal server error' => [500, InternalServerErrorException::class];
        yield 'bad gateway' => [502, ServerException::class];
        yield 'service unavailable' => [503, ServerException::class];
        yield 'unexpected status' => [418, ApiResponseErrorException::class];
    }

    /** @param class-string<\Throwable> $expected */
    #[DataProvider('errorStatuses')]
    public function testMapsStatusAndPreservesCode(int $status, string $expected): void
    {
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response($status, [], '{}'));

        try {
            Client::createWithHttpClient($mock)->game()->missions();
            self::fail('Expected an HTTP error');
        } catch (\Throwable $exception) {
            self::assertInstanceOf($expected, $exception);
            self::assertSame($status, $exception->getCode());
        }
    }

    public function testRateLimitExposesRetryAfter(): void
    {
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(429, ['Retry-After' => 'Wed, 21 Oct 2015 07:28:00 GMT']));

        try {
            Client::createWithHttpClient($mock)->game()->missions();
            self::fail('Expected a rate limit error');
        } catch (RateLimitException $exception) {
            self::assertInstanceOf(ApiResponseErrorException::class, $exception);
            self::assertSame('Wed, 21 Oct 2015 07:28:00 GMT', $exception->getRetryAfter());
        }
    }

    public function testRateLimitWithoutRetryAfter(): void
    {
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(429));

        try {
            Client::createWithHttpClient($mock)->game()->missions();
            self::fail('Expected a rate limit error');
        } catch (RateLimitException $exception) {
            self::assertNull($exception->getRetryAfter());
        }
    }

    public function testRateLimitExposesNumericRetryAfterWithoutConversion(): void
    {
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(429, ['Retry-After' => '120']));

        try {
            Client::createWithHttpClient($mock)->game()->missions();
            self::fail('Expected a rate limit error');
        } catch (RateLimitException $exception) {
            self::assertSame('120', $exception->getRetryAfter());
        }
    }

    public function testPassesOtherSuccessfulStatusesToResponseDecoding(): void
    {
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(201, [], '{"ok":true}'));
        self::assertSame(['ok' => true], Client::createWithHttpClient($mock)->game()->missions());
    }

    public function testEmptySuccessfulResponseIsNotConvertedToAnArray(): void
    {
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(204));
        $this->expectException(ApiResponseErrorException::class);
        Client::createWithHttpClient($mock)->game()->missions();
    }
    public function testPassesThroughOn200(): void
    {
        $this->apiClass = \Hyperplural\WarfaceSdk\Api\User::class;
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(200, ['Content-Type' => 'application/json'], '{}'));

        $client = Client::createWithHttpClient($mock);
        $api = new $this->apiClass($client);

        $this->assertIsArray($api->stat('nick'));
    }

    public function testBadRequestWithJsonMessage(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Пользователь не найден');

        $this->apiClass = \Hyperplural\WarfaceSdk\Api\User::class;
        $mock = new \Http\Mock\Client();
        $mock->addResponse(
            new Response(
                400,
                ['Content-Type' => 'application/json'],
                '{"message":"Пользователь не найден","code":400}'
            )
        );

        $client = Client::createWithHttpClient($mock);
        (new $this->apiClass($client))->stat('');
    }

    public function testBadRequestWithNonJsonBody(): void
    {
        $this->expectException(BadRequestException::class);

        $this->apiClass = \Hyperplural\WarfaceSdk\Api\User::class;
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(400, ['Content-Type' => 'text/plain'], 'Bad request'));

        $client = Client::createWithHttpClient($mock);
        (new $this->apiClass($client))->stat('');
    }

    public function testBadRequestWithMalformedJsonFallsBackToRawBody(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('{"message":');

        $this->apiClass = \Hyperplural\WarfaceSdk\Api\User::class;
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(400, ['Content-Type' => 'application/json'], '{"message":'));

        $client = Client::createWithHttpClient($mock);
        (new $this->apiClass($client))->stat('');
    }

    public function testNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $this->apiClass = \Hyperplural\WarfaceSdk\Api\User::class;
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(404, ['Content-Type' => 'application/json'], '{}'));

        $client = Client::createWithHttpClient($mock);
        (new $this->apiClass($client))->stat('nick');
    }

    public function testInternalServerErrorException(): void
    {
        $this->expectException(InternalServerErrorException::class);

        $this->apiClass = \Hyperplural\WarfaceSdk\Api\User::class;
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(500, ['Content-Type' => 'application/json'], '{}'));

        $client = Client::createWithHttpClient($mock);
        (new $this->apiClass($client))->stat('nick');
    }

    public function testUnknownApiResponseError(): void
    {
        $this->expectException(ApiResponseErrorException::class);

        $this->apiClass = \Hyperplural\WarfaceSdk\Api\User::class;
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(418, ['Content-Type' => 'application/json'], '{}'));

        $client = Client::createWithHttpClient($mock);
        (new $this->apiClass($client))->stat('nick');
    }
}
