<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Business\Hyperf\Rpc\Mcp\Server;

use FastRoute\Dispatcher;
use Business\Hyperf\Rpc\Mcp\Server\Router\DispatcherFactory;
use Psr\Http\Message\ServerRequestInterface;
use Hyperf\JsonRpc\CoreMiddleware;
use function Hyperf\Support\make;

class HttpCoreMiddleware extends \Hyperf\JsonRpc\HttpCoreMiddleware
{
    protected function createDispatcher(string $serverName): Dispatcher
    {
        $factory = make(DispatcherFactory::class, [
            'pathGenerator' => $this->protocol->getPathGenerator(),
        ]);
        return $factory->getDispatcher($serverName);
    }
}
