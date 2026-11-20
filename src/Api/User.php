<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Api;

use Hyperplural\WarfaceSdk\Enum\EntityList;

class User extends AbstractApi implements UserInterface
{
    public function achievements(string $name): array
    {
        return $this->getByMethod(__FUNCTION__, ['name' => $name]);
    }

    public function stat(string $name): array
    {
        return $this->getByMethod(__FUNCTION__, ['name' => $name]);
    }

    protected function entity(): EntityList
    {
        return EntityList::USER;
    }
}
