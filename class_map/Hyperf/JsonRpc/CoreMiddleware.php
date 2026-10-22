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

namespace Hyperf\JsonRpc;

use Closure;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Rpc\Protocol;
use Hyperf\Validation\ValidationException;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

use function Hyperf\Collection\data_get;

class CoreMiddleware extends \Hyperf\RpcServer\CoreMiddleware
{
    protected ResponseBuilder $responseBuilder;

    public function __construct(ContainerInterface $container, Protocol $protocol, ResponseBuilder $builder, string $serverName)
    {
        parent::__construct($container, $protocol, $serverName);
        $this->responseBuilder = $builder;
    }

    /**
     * AOP update jmiy 2026-10-21
     * @param Dispatched $dispatched
     * @param ServerRequestInterface $request
     * @return mixed
     * @throws Throwable
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function handleFound(Dispatched $dispatched, ServerRequestInterface $request): mixed
    {
        if ($dispatched->handler->callback instanceof Closure) {
            $callback = $dispatched->handler->callback;
            $response = $callback();
        } else {
            [$controller, $action] = $this->prepareHandler($dispatched->handler->callback);
            $controllerInstance = $this->container->get($controller);
            if (! method_exists($controller, $action)) {
                // Route found, but the handler does not exist.
                return $this->responseBuilder->buildErrorResponse($request, ResponseBuilder::INTERNAL_ERROR);
            }

            /* AOP handle start */
            $arguments = $request->getParsedBody();
            if ($action == '__call') {
                $key = '__method__';
                $method = array_key_exists($key, $dispatched->params) ? $dispatched->params[$key] : '';

//                $method = rawurldecode($method);
//                if (json_validate($method)) {
//                    $contract = json_decode($method,true);
//                    $service = data_get($contract, [BusinessConstant::SERVICE], '');
//                    $method = data_get($contract, [BusinessConstant::METHOD], '');
//
//                }
//
//                $separator = '::';
//                if (str_contains($method, $separator)) {// 如果使用 model 默认数据库连接，就直接创建model对象即可
//                    [$controller, $action] = explode($separator, $method);
//                    $controllerInstance = $this->container->get($controller);
//                }

                if (! method_exists($controller, $method)) {
                    $arguments = [$method, $arguments];
                } else {
                    $action = $method;
                }
            }

            try {
//                $parameters = $this->parseMethodParameters($controller, $action, $request->getParsedBody());
                $parameters = $this->parseMethodParameters($controller, $action, $arguments);
                /* AOP handle end */
            } catch (InvalidArgumentException) {
                return $this->responseBuilder->buildErrorResponse($request, ResponseBuilder::INVALID_PARAMS);
            } catch (ValidationException $validationException) {
                return $this->responseBuilder->buildErrorResponse($request, ResponseBuilder::INVALID_PARAMS, $validationException);
            }

            try {
                $response = $controllerInstance->{$action}(...$parameters);
            } catch (Throwable $exception) {
                $response = $this->responseBuilder->buildErrorResponse($request, ResponseBuilder::SERVER_ERROR, $exception);
                $this->responseBuilder->persistToContext($response);

                throw $exception;
            }
        }
        return $response;
    }

    protected function handleNotFound(ServerRequestInterface $request): mixed
    {
        return $this->responseBuilder->buildErrorResponse($request, ResponseBuilder::METHOD_NOT_FOUND);
    }

    protected function handleMethodNotAllowed(array $methods, ServerRequestInterface $request): mixed
    {
        return $this->handleNotFound($request);
    }

    protected function transferToResponse($response, ServerRequestInterface $request): ResponsePlusInterface
    {
        return $this->responseBuilder->buildResponse($request, $response);
    }
}
