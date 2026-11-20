<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Tests\Api;

use GuzzleHttp\Psr7\Response;
use Http\Mock\Client as MockClient;
use Hyperplural\WarfaceSdk\Client;
use Hyperplural\WarfaceSdk\Enum\GameClass;
use Hyperplural\WarfaceSdk\Enum\RatingLeague;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HttpContractTest extends TestCase
{
    /**
     * @param array<string, string> $expectedParameters
     */
    #[DataProvider('requestCases')]
    public function testRequestContract(
        string $operation,
        string $path,
        array $expectedParameters,
        string $namePrefix
    ): void {
        $mock = new MockClient();
        $mock->addResponse(new Response(200, ['Content-Type' => 'application/json'], '{"result":"ok"}'));
        $client = Client::createWithHttpClient($mock);

        $result = match ($operation) {
            'achievement' => $client->achievement()->catalog(),
            'clan' => $client->clan()->members('A & B'),
            'game' => $client->game()->missions(),
            'rating-clan' => $client->rating()->clan(),
            'monthly' => $client->rating()->monthly('A & B', RatingLeague::ELITE, 3),
            'monthly-default' => $client->rating()->monthly(),
            'monthly-none' => $client->rating()->monthly('', RatingLeague::NONE),
            'monthly-zero-clan' => $client->rating()->monthly('0'),
            'top100' => $client->rating()->top100(GameClass::SNIPER),
            'top100-none' => $client->rating()->top100(GameClass::NONE),
            'top100-default' => $client->rating()->top100(),
            'stat' => $client->user()->stat('A & B'),
            'stat-empty' => $client->user()->stat(''),
            'achievements' => $client->user()->achievements('A & B'),
            default => throw new \LogicException('Unknown test operation'),
        };

        $this->assertSame(['result' => 'ok'], $result);
        $requests = $mock->getRequests();
        $this->assertCount(1, $requests);
        $request = $requests[0];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https', $request->getUri()->getScheme());
        $this->assertSame('api.warface.ru', $request->getUri()->getHost());
        $this->assertSame($path, $request->getUri()->getPath());

        $query = $request->getUri()->getQuery();
        parse_str($query, $parameters);
        $this->assertArrayHasKey('name', $parameters);
        $this->assertIsString($parameters['name']);
        $this->assertMatchesRegularExpression(
            '/^' . preg_quote($namePrefix, '/') . '<[0-9a-f]{64}$/',
            $parameters['name']
        );
        unset($parameters['name']);
        $this->assertSame($expectedParameters, $parameters);
        $this->assertStringContainsString('%3C', $query);
        if ($namePrefix !== '' || ($expectedParameters['clan'] ?? null) === 'A & B') {
            $this->assertStringContainsString('A%20%26%20B', $query);
        }
    }

    /**
     * @return array<string, array{string, string, array<string, string>, string}>
     */
    public static function requestCases(): array
    {
        return [
            'achievement catalog' => ['achievement', 'achievement/catalog', [], ''],
            'clan members' => ['clan', 'clan/members', ['clan' => 'A & B'], ''],
            'game missions' => ['game', 'game/missions', [], ''],
            'rating clan' => ['rating-clan', 'rating/clan', [], ''],
            'rating monthly with filters' => [
                'monthly', 'rating/monthly', ['page' => '3', 'clan' => 'A & B', 'league' => '1'], '',
            ],
            'rating monthly defaults' => ['monthly-default', 'rating/monthly', ['page' => '0'], ''],
            'rating monthly empty and none' => ['monthly-none', 'rating/monthly', ['page' => '0'], ''],
            'rating monthly zero clan' => ['monthly-zero-clan', 'rating/monthly', ['page' => '0', 'clan' => '0'], ''],
            'rating top100 with class' => ['top100', 'rating/top100', ['class' => '3'], ''],
            'rating top100 none' => ['top100-none', 'rating/top100', [], ''],
            'rating top100 default' => ['top100-default', 'rating/top100', [], ''],
            'user stat' => ['stat', 'user/stat', [], 'A & B'],
            'user stat empty name' => ['stat-empty', 'user/stat', [], ''],
            'user achievements' => ['achievements', 'user/achievements', [], 'A & B'],
        ];
    }
}
