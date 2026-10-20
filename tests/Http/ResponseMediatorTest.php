<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Tests\Http;

use GuzzleHttp\Psr7\Response;
use Hyperplural\WarfaceSdk\Client;
use Hyperplural\WarfaceSdk\Exception\ApiResponseErrorException;
use Hyperplural\WarfaceSdk\Tests\TestCase;

final class ResponseMediatorTest extends TestCase
{
    public function testThrowsOnInvalidJson(): void
    {
        $this->expectException(ApiResponseErrorException::class);

        $this->apiClass = \Hyperplural\WarfaceSdk\Api\User::class;
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(200, ['Content-Type' => 'application/json'], 'invalid-json'));

        $client = Client::createWithHttpClient($mock);
        (new $this->apiClass($client))->stat('nick');
    }
}
