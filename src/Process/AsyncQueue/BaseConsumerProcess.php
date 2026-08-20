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

namespace Business\Hyperf\Process\AsyncQueue;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Process\ConsumerProcess;
use Hyperf\Contract\ConfigInterface;
use Swoole\Coroutine\Server as CoServer;
use Swoole\Server;

use function Hyperf\Collection\data_get;
use function Hyperf\Coroutine\go;

class BaseConsumerProcess extends ConsumerProcess
{
    /**
     * Determine if the process should start ?
     * @param CoServer|Server $server
     */
    public function isEnable($server): bool
    {
        return (bool) data_get($this->config, ['processEnable'], true);
    }

    public function handle(): void
    {
        $config = $this->container->get(ConfigInterface::class);
        $_configs = $config->get('async_queue', []);

        $factory = $this->container->get(DriverFactory::class);

//        $dd = [];
        foreach ($_configs as $_pool => $item) {
            if (
                $_pool != $this->pool
                && data_get($item, 'processEnable') === false
                && in_array($this->pool, data_get($item, 'runProcess', []))
            ) {
                $driver = $factory->get($_pool);
                go(function () use ($driver) {
                    $driver->consume();
                });
//                $dd[] = $_pool;
            }
        }

//        var_dump([$this->pool=>$dd]);

        parent::handle();
    }
}
