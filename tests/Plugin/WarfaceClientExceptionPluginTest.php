<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Tests\Plugin;

use GuzzleHttp\Psr7\Response;
use Hyperplural\WarfaceSdk\Client;
use Hyperplural\WarfaceSdk\Exception\ApiResponseErrorException;
use Hyperplural\WarfaceSdk\Exception\BadRequestException;
use Hyperplural\WarfaceSdk\Exception\InternalServerErrorException;
use Hyperplural\WarfaceSdk\Exception\NotFoundException;
use Hyperplural\WarfaceSdk\Tests\TestCase;

final class WarfaceClientExceptionPluginTest extends TestCase
{
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
