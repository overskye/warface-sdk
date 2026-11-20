<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Api;

use Hyperplural\WarfaceSdk\Enum\EntityList;
use Hyperplural\WarfaceSdk\Enum\GameClass;
use Hyperplural\WarfaceSdk\Enum\RatingLeague;
use InvalidArgumentException;

class Rating extends AbstractApi implements RatingInterface
{
    public function clan(): array
    {
        return $this->getByMethod(__FUNCTION__);
    }

    public function monthly(?string $clan = null, ?RatingLeague $league = null, int $page = 0): array
    {
        if ($page < 0) {
            throw new InvalidArgumentException('Page must not be negative');
        }

        $params = ['page' => $page];

        if ($clan !== null && $clan !== '') {
            $params['clan'] = $clan;
        }

        $leagueValue = $league?->value;
        if ($leagueValue !== null && $leagueValue !== RatingLeague::NONE->value) {
            $params['league'] = $leagueValue;
        }

        return $this->getByMethod(__FUNCTION__, $params);
    }

    public function top100(?GameClass $class = null): array
    {
        $params = [];

        $classValue = $class?->value;
        if ($classValue !== null && $classValue !== GameClass::NONE->value) {
            $params['class'] = $classValue;
        }

        return $this->getByMethod(__FUNCTION__, $params);
    }

    protected function entity(): EntityList
    {
        return EntityList::RATING;
    }
}
