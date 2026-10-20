<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk;

use Http\Client\Common\HttpMethodsClientInterface;
use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\UriInterface;
use Hyperplural\WarfaceSdk\Api\AbstractApi;
use Hyperplural\WarfaceSdk\Api\Achievement;
use Hyperplural\WarfaceSdk\Api\AchievementInterface;
use Hyperplural\WarfaceSdk\Api\Clan;
use Hyperplural\WarfaceSdk\Api\ClanInterface;
use Hyperplural\WarfaceSdk\Api\Game;
use Hyperplural\WarfaceSdk\Api\GameInterface;
use Hyperplural\WarfaceSdk\Api\Rating;
use Hyperplural\WarfaceSdk\Api\RatingInterface;
use Hyperplural\WarfaceSdk\Api\User;
use Hyperplural\WarfaceSdk\Api\UserInterface;
use Hyperplural\WarfaceSdk\Enum\EntityList;
use Hyperplural\WarfaceSdk\Enum\HostList;
use Hyperplural\WarfaceSdk\Exception\InvalidApiEndpointException;
use Hyperplural\WarfaceSdk\HttpClient\ClientBuilder;
use Hyperplural\WarfaceSdk\HttpClient\Plugin\BypassTimeoutResponsePlugin;
use Hyperplural\WarfaceSdk\HttpClient\Plugin\WarfaceClientExceptionPlugin;

/**
 * @method AchievementInterface achievement() Achievement branch
 * @method ClanInterface clan() Clan branch
 * @method GameInterface game() Game branch
 * @method RatingInterface rating() Rating branch
 * @method UserInterface user() User branch
 */
final class Client
{
    private readonly ClientBuilder $httpClientBuilder;

    public function __construct(?ClientBuilder $httpClientBuilder = null)
    {
        $builder = clone ($httpClientBuilder ?? new ClientBuilder());
        $this->httpClientBuilder = $builder;

        // Set API host (CIS)
        $builder->addPlugin(new AddHostPlugin($this->makeHostUri(HostList::CIS)));

        // Always bypass API timeout logic by default
        $builder->addPlugin(new BypassTimeoutResponsePlugin());

        $builder->addPlugin(
            new HeaderDefaultsPlugin([
                'User-Agent' => 'Hyperplural Warface SDK (PHP)',
            ])
        );

        $builder->addPlugin(new WarfaceClientExceptionPlugin());
    }

    /**
     * @param array<int, mixed> $args
     * @throws InvalidApiEndpointException
     */
    public function __call(string $entity, array $args = []): AbstractApi
    {
        return match ($entity) {
            EntityList::ACHIEVEMENT->value => new Achievement($this),
            EntityList::CLAN->value        => new Clan($this),
            EntityList::GAME->value        => new Game($this),
            EntityList::RATING->value      => new Rating($this),
            EntityList::USER->value        => new User($this),
            default                 => throw new InvalidApiEndpointException('Call unknown entity'),
        };
    }

    public static function createWithHttpClient(ClientInterface $httpClient): self
    {
        $builder = new ClientBuilder($httpClient);

        return new self($builder);
    }

    public function getHttpClient(): HttpMethodsClientInterface
    {
        return $this->getHttpClientBuilder()->getHttpClient();
    }

    protected function getHttpClientBuilder(): ClientBuilder
    {
        return $this->httpClientBuilder;
    }

    private function makeHostUri(HostList $host): UriInterface
    {
        return Psr17FactoryDiscovery::findUriFactory()->createUri('https://' . $host->value);
    }
}
