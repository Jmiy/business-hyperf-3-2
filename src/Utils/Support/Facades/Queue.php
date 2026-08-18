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

namespace Business\Hyperf\Utils\Support\Facades;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\Context\ApplicationContext;

use function Hyperf\Support\make;

class Queue
{
    /**
     * public static function connection($queue = null): DriverInterface.
     * @param null|mixed $queue 消息队列配置名称 默认：null(使用默认消息队列：default)
     */
    public static function connection(mixed $queue = null): DriverInterface
    {
        return ApplicationContext::getContainer()->get(DriverFactory::class)->get($queue ?? 'default');
    }

    /**
     * 生产消息 public static function push($job, $data = null, int $delay = 0, $connection = null, $channel = null): bool.
     * @param mixed $job job对象|类
     * @param null|mixed $data job类 参数
     * @param int $delay 延时执行时间 (单位：秒)
     * @param null|mixed $connection 消息队列配置名称 默认：null(使用默认消息队列：default)
     * @param null|mixed $channel 队列名 默认取 $connection 对应的配置的 channel 队列名 暂时不支持动态修改
     */
    public static function push(mixed $job, mixed $data = null, int $delay = 0, mixed $connection = null, mixed $channel = null): bool
    {
        if (! is_object($job)) {
            $job = make($job, $data);
        }
        return static::connection($connection)->push($job, $delay);
    }
}
