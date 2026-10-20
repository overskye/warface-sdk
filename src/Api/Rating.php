<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Api;

use Hyperplural\WarfaceSdk\Enum\EntityList;
use Hyperplural\WarfaceSdk\Enum\GameClass;
use Hyperplural\WarfaceSdk\Enum\RatingLeague;

class Rating extends AbstractApi implements RatingInterface
{
    public function clan(): array
    {
        return $this->getByMethod(__FUNCTION__);
    }

    public function monthly(?string $clan = null, ?RatingLeague $league = null, int $page = 0): array
    {
        $params = ['page' => $page];

        if ($clan !== null && $clan !== '') {
            $params['clan'] = $clan;
        }

        if ($league instanceof \Hyperplural\WarfaceSdk\Enum\RatingLeague && $league !== RatingLeague::NONE) {
            $params['league'] = $league->value;
        }

        return $this->getByMethod(__FUNCTION__, $params);
    }

    public function top100(?GameClass $class = null): array
    {
        $params = [];

        if ($class instanceof \Hyperplural\WarfaceSdk\Enum\GameClass && $class !== GameClass::NONE) {
            $params['class'] = $class->value;
        }

        return $this->getByMethod(__FUNCTION__, $params);
    }

    protected function entity(): EntityList
    {
        return EntityList::RATING;
    }
}
