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

namespace Business\Hyperf\Service\Traits;

use Business\Hyperf\Constants\Constant;
use Hyperf\Collection\Arr;
use Hyperf\Coroutine\Coroutine;
use Throwable;

use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Config\config;

trait Queue
{
    /**
     * 生产消息.
     * @param callable $callback 回调闭包|回调类
     * @param null|string $method 类方法
     * @param null|array $parameters 方法参数
     * @param null|int $delay 延迟时间 默认：0
     * @param null|string $queueConnection 消息队列
     * @param null|array|int[] $extData 扩展参数 有用控制重试次数 睡眠时间等
     * @param null|array $request 请求
     * @throws Throwable
     */
    public static function push(
        $callback,
        ?string $method = '',
        ?array $parameters = [],
        ?int $delay = 0,
        ?string $queueConnection = Constant::QUEUE_CONNECTION_DEFAULT,
        ?array $extData = [
            Constant::RETRY_MAX => 3,
            Constant::SLEEP_MIN => 0,
            Constant::SLEEP_MAX => 10,
        ],
        ?array $request = null
    ): bool {
        $extData = Arr::collapse([
            [
                Constant::QUEUE_CONNECTION => $queueConnection,
                Constant::QUEUE_DELAY => $delay === null ? rand(1, 10) : $delay,
            ],
            $extData,
        ]);

        $job = getJobData($callback, $method, $parameters, $request, $extData);

        //        return pushQueue($job);

        $isPush = true;
        $retryMax = data_get($extData, Constant::RETRY_MAX, 3);
        $sleepMin = data_get($extData, Constant::SLEEP_MIN, 0);
        $sleepMax = data_get($extData, Constant::SLEEP_MAX, 1);
        for ($i = 0; $i < $retryMax; ++$i) {
            $isPush = pushQueue($job);
            if ($isPush) {
                break;
            }
            // 如果压入队列失败，就睡眠 $sleepMin-$sleepMax，等待redis恢复
            Coroutine::sleep(rand($sleepMin, $sleepMax));
        }

        return $isPush;
    }

    /**
     * 获取队列基本数据.
     * @param mixed $queue
     * @return string
     */
    public static function getQueueData($queue = Constant::QUEUE_EBAY)
    {
        $poolName = config('async_queue.' . $queue . '.redis.pool');

        return [
            Constant::QUEUE => $queue,
            Constant::POOL_NAME => $poolName,
        ];
    }
}
