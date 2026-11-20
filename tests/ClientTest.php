<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Tests;

use Hyperplural\WarfaceSdk\Api\Achievement;
use Hyperplural\WarfaceSdk\Api\ClanInterface;
use Hyperplural\WarfaceSdk\Api\GameInterface;
use Hyperplural\WarfaceSdk\Api\RatingInterface;
use Hyperplural\WarfaceSdk\Api\UserInterface;
use Hyperplural\WarfaceSdk\Client;
use GuzzleHttp\Psr7\Response;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Http\Mock\Client as MockClient;
use Hyperplural\WarfaceSdk\HttpClient\ClientBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    #[DataProvider('builderCloneTiming')]
    public function testBuilderCloneKeepsIndependentPlugins(bool $buildBeforeClone): void
    {
        $transport = new MockClient();
        $transport->addResponse(new Response(200, [], '{}'));
        $transport->addResponse(new Response(200, [], '{}'));
        $builder = new ClientBuilder($transport);
        if ($buildBeforeClone) {
            $builder->getHttpClient();
        }

        $clone = clone $builder;
        $clone->addPlugin(new HeaderDefaultsPlugin(['X-Clone' => 'clone']));
        $builder->getHttpClient()->send('GET', 'https://example.com/');
        $clone->getHttpClient()->send('GET', 'https://example.com/');

        $requests = $transport->getRequests();
        self::assertCount(2, $requests);
        self::assertSame('', $requests[0]->getHeaderLine('X-Clone'));
        self::assertSame('clone', $requests[1]->getHeaderLine('X-Clone'));
    }

    /** @return array<string, array{bool}> */
    public static function builderCloneTiming(): array
    {
        return ['before build' => [false], 'after build' => [true]];
    }

    public function testClientIsIndependentOfSubsequentBuilderChanges(): void
    {
        $transport = new MockClient();
        $transport->addResponse(new Response(200, [], '{}'));
        $transport->addResponse(new Response(200, [], '{}'));
        $builder = new ClientBuilder($transport);
        $client = new Client($builder);

        $builder->addPlugin(new HeaderDefaultsPlugin(['X-Later' => 'added']));
        $client->game()->missions();
        $builder->getHttpClient()->send('GET', 'https://example.com/');

        $requests = $transport->getRequests();
        self::assertCount(2, $requests);
        self::assertSame('', $requests[0]->getHeaderLine('X-Later'));
        self::assertSame('added', $requests[1]->getHeaderLine('X-Later'));
    }

    public function testBuilderCachesAndInvalidatesItsStackIndependentlyOfClone(): void
    {
        $builder = new \Hyperplural\WarfaceSdk\HttpClient\ClientBuilder(new \Http\Mock\Client());
        $first = $builder->getHttpClient();
        self::assertSame($first, $builder->getHttpClient());

        $clone = clone $builder;
        $clone->addPlugin(new \Hyperplural\WarfaceSdk\HttpClient\Plugin\RateLimitWorkaroundPlugin());
        self::assertNotSame($first, $clone->getHttpClient());
        self::assertSame($first, $builder->getHttpClient());

        $builder->addPlugin(new \Hyperplural\WarfaceSdk\HttpClient\Plugin\RateLimitWorkaroundPlugin());
        self::assertNotSame($first, $builder->getHttpClient());
    }

    public function testGetsInstancesFromTheClient(): void
    {
        $client = new Client();

        $this->assertInstanceOf(Achievement::class, $client->achievement());
        $this->assertInstanceOf(ClanInterface::class, $client->clan());
        $this->assertInstanceOf(GameInterface::class, $client->game());
        $this->assertInstanceOf(RatingInterface::class, $client->rating());
        $this->assertInstanceOf(UserInterface::class, $client->user());
    }
}
