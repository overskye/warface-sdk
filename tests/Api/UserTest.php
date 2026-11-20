<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Tests\Api;

use Hyperplural\WarfaceSdk\Api\User;
use Hyperplural\WarfaceSdk\Tests\TestCase;

final class UserTest extends TestCase
{
    public function testCanRequestUserStat(): void
    {
        $this->apiClass = User::class;
        /** @var \Hyperplural\WarfaceSdk\Api\User $api */
        $api = $this->getApiWithFixture('user/stat.json');

        $name = 'ГрозовоеОблако';
        $element = $api->stat($name);

        $this->assertIsArray($element);
        $this->assertArrayHasKey('user_id', $element);
        $this->assertArrayHasKey('nickname', $element);
        $this->assertSame($name, $element['nickname']);
        $this->assertArrayHasKey('experience', $element);
        $this->assertArrayHasKey('rank_id', $element);
        $this->assertArrayHasKey('kill', $element);
        $this->assertArrayHasKey('friendly_kills', $element);
        $this->assertArrayHasKey('kills', $element);
        $this->assertArrayHasKey('death', $element);
        $this->assertArrayHasKey('pvp', $element);
        $this->assertArrayHasKey('pve_kill', $element);
        $this->assertArrayHasKey('pve_friendly_kills', $element);
        $this->assertArrayHasKey('pve_kills', $element);
        $this->assertArrayHasKey('pve_death', $element);
        $this->assertArrayHasKey('pve', $element);
        $this->assertArrayHasKey('playtime', $element);
        $this->assertArrayHasKey('playtime_h', $element);
        $this->assertArrayHasKey('playtime_m', $element);
        $this->assertArrayHasKey('favoritPVP', $element);
        $this->assertArrayHasKey('favoritPVE', $element);
        $this->assertArrayHasKey('pve_wins', $element);
        $this->assertArrayHasKey('pvp_wins', $element);
        $this->assertArrayHasKey('pvp_lost', $element);
        $this->assertArrayHasKey('pve_lost', $element);
        $this->assertArrayHasKey('pve_all', $element);
        $this->assertArrayHasKey('pvp_all', $element);
        $this->assertArrayHasKey('pvpwl', $element);
        $this->assertArrayHasKey('full_response', $element);
    }

    public function testCanRequestUserAchievements(): void
    {
        $this->apiClass = User::class;
        /** @var \Hyperplural\WarfaceSdk\Api\User $api */
        $api = $this->getApiWithFixture('user/achievements.json');

        $name = 'ГрозовоеОблако';
        /** @var array<int, array<string, mixed>> $list */
        $list = $api->achievements($name);
        $element = $list[0] ?? null;

        $this->assertIsArray($element);
        $this->assertArrayHasKey('achievement_id', $element);
        $this->assertArrayHasKey('progress', $element);
        $this->assertArrayHasKey('completion_time', $element);
    }
}
