<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Api;

use Hyperplural\WarfaceSdk\Enum\EntityList;

class Clan extends AbstractApi implements ClanInterface
{
    public function members(string $clan): array
    {
        return $this->getByMethod(__FUNCTION__, ['clan' => $clan]);
    }

    protected function entity(): EntityList
    {
        return EntityList::CLAN;
    }
}
