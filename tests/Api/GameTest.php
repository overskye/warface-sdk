<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Tests\Api;

use Hyperplural\WarfaceSdk\Api\Game;
use Hyperplural\WarfaceSdk\Tests\TestCase;

final class GameTest extends TestCase
{
    public function testCanRequestGameMissions(): void
    {
        $this->apiClass = Game::class;
        /** @var \Hyperplural\WarfaceSdk\Api\Game $api */
        $api = $this->getApiWithFixture('game/missions.json');

        /** @var array<int, array<string, mixed>> $list */
        $list = $api->missions();
        $element = $list[0] ?? null;

        $this->assertIsArray($element);
        $this->assertArrayHasKey('game_mode', $element);
        $this->assertArrayHasKey('name', $element);
        $this->assertArrayHasKey('mission_type', $element);
    }
}
