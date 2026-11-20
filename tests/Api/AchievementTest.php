<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Tests\Api;

use Hyperplural\WarfaceSdk\Api\Achievement;
use Hyperplural\WarfaceSdk\Tests\TestCase;

final class AchievementTest extends TestCase
{
    public function testCanRequestCatalogOfAchievements(): void
    {
        $this->apiClass = Achievement::class;
        /** @var \Hyperplural\WarfaceSdk\Api\Achievement $api */
        $api = $this->getApiWithFixture('achievement/catalog.json');

        /** @var array<int, array<string, mixed>> $list */
        $list = $api->catalog();
        $element = $list[0] ?? null;

        $this->assertIsArray($element);
        $this->assertArrayHasKey('id', $element);
        $this->assertArrayHasKey('name', $element);
    }
}
