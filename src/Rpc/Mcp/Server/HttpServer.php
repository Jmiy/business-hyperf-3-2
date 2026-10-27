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

use Hyperf\ExceptionHandler\ExceptionHandlerDispatcher;
use Hyperf\HttpServer\Contract\CoreMiddlewareInterface;
use Hyperf\HttpServer\ResponseEmitter;
use Hyperf\HttpServer\Server;
use Hyperf\JsonRpc\HttpServer as JsonRpcHttpServer;
use Hyperf\JsonRpc\ResponseBuilder;
use Hyperf\Rpc\Protocol;
use Hyperf\Rpc\ProtocolManager;
use Hyperf\RpcServer\RequestDispatcher;
use Psr\Container\ContainerInterface;

use function Hyperf\Support\make;

class HttpServer extends JsonRpcHttpServer
{
    public function __construct(
        ContainerInterface $container,
        RequestDispatcher $dispatcher,
        ExceptionHandlerDispatcher $exceptionHandlerDispatcher,
        ResponseEmitter $responseEmitter,
        ProtocolManager $protocolManager
    ) {
        //        parent::__construct($container, $dispatcher, $exceptionHandlerDispatcher, $responseEmitter, $protocolManager);
        Server::__construct($container, $dispatcher, $exceptionHandlerDispatcher, $responseEmitter);
        $this->protocol = new Protocol($container, $protocolManager, 'mcp-jsonrpc-http');
        $this->packer = $this->protocol->getPacker();
        $this->responseBuilder = make(ResponseBuilder::class, [
            'dataFormatter' => $this->protocol->getDataFormatter(),
            'packer' => $this->packer,
        ]);
    }

    //    protected function createCoreMiddleware(): CoreMiddlewareInterface
    //    {
    //        return new HttpCoreMiddleware($this->container, $this->protocol, $this->responseBuilder, $this->serverName);
    //    }
}
