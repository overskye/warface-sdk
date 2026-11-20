<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Tests\Plugin;

use Hyperplural\WarfaceSdk\Client;
use Hyperplural\WarfaceSdk\Tests\TestCase;
use GuzzleHttp\Psr7\Response;

use function parse_str;

final class BypassTimeoutResponsePluginTest extends TestCase
{
    public function testPreservesParametersAndGeneratesNewSuffixForEachRequest(): void
    {
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(200, [], '[]'));
        $mock->addResponse(new Response(200, [], '[]'));
        $client = Client::createWithHttpClient($mock);

        $client->user()->stat('A B');
        $client->user()->stat('A B');

        $queries = array_map(static fn ($request) => $request->getUri()->getQuery(), $mock->getRequests());
        self::assertCount(2, $queries);
        self::assertStringContainsString('name=A%20B%3C', $queries[0]);
        parse_str($queries[0], $first);
        parse_str($queries[1], $second);
        self::assertIsString($first['name']);
        self::assertMatchesRegularExpression('/^A B<[a-f0-9]{64}$/D', $first['name']);
        self::assertNotSame($first['name'], $second['name']);
    }

    public function testEmptyAndZeroNamesAreReplacedBySuffix(): void
    {
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(200, [], '[]'));
        $mock->addResponse(new Response(200, [], '[]'));
        $client = Client::createWithHttpClient($mock);

        $client->user()->stat('');
        $client->user()->stat('0');

        foreach ($mock->getRequests() as $request) {
            parse_str($request->getUri()->getQuery(), $params);
            self::assertIsString($params['name']);
            self::assertMatchesRegularExpression('/^<[a-f0-9]{64}$/D', $params['name']);
        }
    }

    public function testLegacyPluginRemainsAvailable(): void
    {
        self::assertInstanceOf(
            \Hyperplural\WarfaceSdk\HttpClient\Plugin\RateLimitWorkaroundPlugin::class,
            new \Hyperplural\WarfaceSdk\HttpClient\Plugin\BypassTimeoutResponsePlugin()
        );
    }

    public function testKeepsOtherParametersWhenNameIsAnArray(): void
    {
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(200, [], '[]'));
        $client = Client::createWithHttpClient($mock);

        $client->getHttpClient()->send('GET', '/game/missions?name%5B%5D=old&clan=A%20B&value=%2B');

        $query = $mock->getRequests()[0]->getUri()->getQuery();
        parse_str($query, $params);
        self::assertIsString($params['name']);
        self::assertMatchesRegularExpression('/^<[a-f0-9]{64}$/D', $params['name']);
        self::assertSame('A B', $params['clan']);
        self::assertSame('+', $params['value']);
        self::assertStringContainsString('clan=A%20B', $query);
        self::assertStringContainsString('value=%2B', $query);
    }

    public function testAppendsRandomSuffixWhenNamePresent(): void
    {
        $this->apiClass = \Hyperplural\WarfaceSdk\Api\User::class;
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(200, ['Content-Type' => 'application/json'], '{}'));

        $client = Client::createWithHttpClient($mock);
        (new $this->apiClass($client))->stat('Nick');

        $requests = $mock->getRequests();
        $this->assertNotEmpty($requests);
        $query = $requests[0]->getUri()->getQuery();
        parse_str($query, $params);
        $this->assertArrayHasKey('name', $params);
        $this->assertIsString($params['name']);
        $this->assertTrue(str_starts_with($params['name'], 'Nick'));
        $this->assertGreaterThan(strlen('Nick'), strlen($params['name']));
    }

    public function testAddsNameWhenAbsent(): void
    {
        $this->apiClass = \Hyperplural\WarfaceSdk\Api\Game::class;
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(200, ['Content-Type' => 'application/json'], '[]'));

        $client = Client::createWithHttpClient($mock);
        (new $this->apiClass($client))->missions();

        $requests = $mock->getRequests();
        $this->assertNotEmpty($requests);
        $query = $requests[0]->getUri()->getQuery();
        parse_str($query, $params);
        $this->assertArrayHasKey('name', $params);
        $this->assertIsString($params['name']);
        $this->assertNotEmpty($params['name']);
    }
}
