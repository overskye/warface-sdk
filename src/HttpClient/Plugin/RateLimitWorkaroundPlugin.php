<?php

declare(strict_types=1);

namespace Hyperplural\WarfaceSdk\HttpClient\Plugin;

use Exception;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;

/**
 * Applies the existing workaround for repeated identical Warface API requests that may wait or time out.
 *
 * A random suffix is added to the name query parameter on every request, including endpoints without a name.
 */
class RateLimitWorkaroundPlugin implements Plugin
{
    /** @throws Exception */
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $uri = $request->getUri();
        $code = '<' . bin2hex(random_bytes(32));
        $params = [];
        parse_str($uri->getQuery(), $params);

        $params['name'] = isset($params['name']) && is_string($params['name'])
            && $params['name'] !== '' && $params['name'] !== '0'
            ? $params['name'] . $code
            : $code;

        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return $next($request->withUri($uri->withQuery($query)));
    }
}
