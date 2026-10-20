<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Enum;

enum GameClass: int
{
    case NONE     = 0;
    case RIFLEMAN = 1;
    case MEDIC    = 2;
    case SNIPER   = 3;
    case ENGINEER = 4;
    case SED      = 5;
}
