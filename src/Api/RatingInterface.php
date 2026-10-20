<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\Api;

use Hyperplural\WarfaceSdk\Enum\GameClass;
use Hyperplural\WarfaceSdk\Enum\RatingLeague;
use Hyperplural\WarfaceSdk\Exception\WarfaceApiException;

interface RatingInterface
{
    /**
     * This method returns information about the rating of clans.
     *
     * @return array<string|int, mixed>
     * @throws WarfaceApiException
     */
    public function clan(): array;

    /**
     * This method returns the monthly rating.
     *
     * If the $clan parameter is used, the response from the server will contain data about the selected clan, it will
     * also indicate exactly the league in which this clan is located even if it was not selected in the $league.
     *
     * If only the $league parameter is used, the server will return the top 100 for that league.
     *
     * @return array<string|int, mixed>
     * @see RatingLeague
     * @throws WarfaceApiException
     */
    public function monthly(?string $clan = null, ?RatingLeague $league = null, int $page = 0): array;

    /**
     * This method returns a TOP-100 rating.
     *
     * If the parameter $class is not specified or set 0, the data gets for all classes.
     *
     * @return array<string|int, mixed>
     * @see GameClass
     * @throws WarfaceApiException
     */
    public function top100(?GameClass $class = null): array;
}
