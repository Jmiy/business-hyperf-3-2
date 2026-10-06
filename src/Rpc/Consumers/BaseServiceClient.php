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

namespace Business\Hyperf\Rpc\Consumers;

use Business\Hyperf\Rpc\Exception\ServiceException;
use Hyperf\Codec\Json;
use Hyperf\Contract\IdGeneratorInterface;
use Hyperf\RpcClient\AbstractServiceClient;
use Hyperf\RpcClient\Exception\RequestException;
use Psr\Container\ContainerInterface;

use function Hyperf\Collection\data_get;

class BaseServiceClient extends AbstractServiceClient
{
    public function __construct(ContainerInterface $container, string $serviceName = '', string $protocol = 'jsonrpc-http', string $loadBalancer = 'random')
    {
        $this->serviceName = $serviceName;
        $this->protocol = $protocol;
        $this->loadBalancer = $loadBalancer;

        parent::__construct($container);
    }

    protected function __request(string $method, array $params, ?string $id = null)
    {
        if (! $id && $this->idGenerator instanceof IdGeneratorInterface) {
            $id = $this->idGenerator->generate();
        }
        $response = $this->client->send($this->__generateData($method, $params, $id));
        if (! is_array($response)) {
            throw new RequestException('Invalid response.');
        }

        $response = $this->checkRequestIdAndTryAgain($response, $id);
        if (array_key_exists('result', $response)) {
            return $response['result'];
        }

        if (array_key_exists('error', $response)) {
            $error = data_get($response, ['error'], 0);
            throw new ServiceException($response, Json::encode($response), data_get($error, ['code'], 0));
        }
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->__request($name, $arguments);
    }
}
