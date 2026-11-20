<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Tests\Api;

use GuzzleHttp\Psr7\Response;
use Hyperplural\WarfaceSdk\Api\Rating;
use Hyperplural\WarfaceSdk\Client;
use Hyperplural\WarfaceSdk\Enum\GameClass;
use Hyperplural\WarfaceSdk\Enum\RatingLeague;
use Hyperplural\WarfaceSdk\Tests\TestCase;

final class RatingTest extends TestCase
{
    public function testRejectsNegativePageBeforeSendingRequest(): void
    {
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new Response(200, [], '[]'));
        $api = new Rating(Client::createWithHttpClient($mock));

        try {
            $api->monthly(page: -1);
            $this->fail('A negative page must be rejected');
        } catch (\InvalidArgumentException) {
            $this->assertSame([], $mock->getRequests());
        }
    }

    public function testCanRequestRatingClan(): void
    {
        $this->apiClass = Rating::class;
        /** @var \Hyperplural\WarfaceSdk\Api\Rating $api */
        $api = $this->getApiWithFixture('rating/clan.json');

        /** @var array<int, array<string, mixed>> $list */
        $list = $api->clan();
        $element = $list[0] ?? null;
        $this->assertIsArray($element);
        $this->assertArrayHasKey('clan', $element);
        $this->assertArrayHasKey('clan_leader', $element);
        $this->assertArrayHasKey('points', $element);
        $this->assertArrayHasKey('rank', $element);
    }

    public function testCanRequestRatingMonthly(): void
    {
        $this->apiClass = Rating::class;
        /** @var \Hyperplural\WarfaceSdk\Api\Rating $api */
        $api = $this->getApiWithFixture('rating/monthly_elite.json');

        $league = RatingLeague::ELITE;
        /** @var array<int, array<string, mixed>> $list */
        $list = $api->monthly('', $league);
        $element = $list[0] ?? null;

        $this->assertIsArray($element);
        $this->assertArrayHasKey('clan', $element);
        $this->assertArrayHasKey('clan_leader', $element);
        $this->assertArrayHasKey('members', $element);
        $this->assertArrayHasKey('points', $element);
        $this->assertArrayHasKey('rank', $element);
        $this->assertArrayHasKey('rank_change', $element);
    }

    public function testCanRequestRatingTop100(): void
    {
        $this->apiClass = Rating::class;
        /** @var \Hyperplural\WarfaceSdk\Api\Rating $api */
        $api = $this->getApiWithFixture('rating/top100_sniper.json');

        $class = GameClass::SNIPER;
        /** @var array<int, array<string, mixed>> $list */
        $list = $api->top100($class);
        $element = $list[0] ?? null;

        $this->assertIsArray($element);
        $this->assertArrayHasKey('nickname', $element);
        $this->assertArrayHasKey('clan', $element);
        $this->assertArrayHasKey('class', $element);
        $this->assertSame($class->value, $element['class']);
        $this->assertArrayHasKey('shard', $element);
    }
}
