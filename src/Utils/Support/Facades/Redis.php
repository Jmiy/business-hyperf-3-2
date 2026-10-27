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

use Hyperf\Context\ApplicationContext;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Throwable;

use function Hyperf\Collection\data_get;

class Redis
{
    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     *
     * @throws RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        return static::getRedis()->{$method}(...$args);
    }

    /**
     * 获取redis连接.
     * @param string $poolName 连接池
     * @return RedisProxy
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public static function getRedis(string $poolName = 'default')
    {
        return ApplicationContext::getContainer()->get(RedisFactory::class)->get($poolName);
    }

    /**
     * Add one or more values to a Redis SET key.
     *
     * @param string $key         The key name
     * @param mixed  $value       A value to add to the set.
     * @param mixed $other_values One or more additional values to add
     *
     * @return \Redis|int|false|array The number of values added to the set.
     *
     * @see https://redis.io/docs/latest/commands/sadd/
     *
     * @example
     * $redis->del('myset');
     *
     * $redis->sadd('myset', 'foo', 'bar', 'baz');
     * $redis->sadd('myset', 'foo', 'new');
     */
    public static function sAdd(string $key, array $value, ?int $seconds = null, string $poolName = 'default'): \Redis|int|false|array
    {
        $instance = static::getRedis($poolName);

        if ($seconds === null) {
            return $instance->sAdd($key, ...$value);
        }

        $instance->multi();
        $instance->sAdd($key, ...$value);
        $instance->pexpire($key, $seconds * 1000);
        $manyResult = $instance->exec();

        return [
            'result' => data_get($manyResult, [0]),
            'pexpire' => data_get($manyResult, [1]),
        ];
    }

    /**
     * Add or update one or more hash fields and values
     *
     * @param string $key        The hash to create/update
     * @param array  $fieldvals  An associative array with fields and their values.
     *
     * @return \Redis|bool|array True if the operation was successful
     *
     * @see https://redis.io/docs/latest/commands/hmset/
     *
     * @example $redis->hmset('updates', ['status' => 'starting', 'elapsed' => 0]);
     */
    public static function hmset(string $key, array $fieldvals, ?int $seconds = null, string $poolName = 'default'): \Redis|bool|array
    {
        $instance = static::getRedis($poolName);

        if ($seconds === null) {
            return $instance->hmset($key, $fieldvals);
        }

        $instance->multi();
        $instance->hmset($key, $fieldvals);
        $instance->pexpire($key, $seconds * 1000);
        $manyResult = $instance->exec();

        return [
            'result' => data_get($manyResult, [0]),
            'pexpire' => data_get($manyResult, [1]),
        ];
    }

    /**
     * Increment a hash field's value by an integer
     *
     * @param string $key   The hash to modify
     * @param string $field The field to increment
     * @param int    $value How much to increment the value.
     *
     * @return \Redis|int|false|array The new value of the field.
     *
     * @see https://redis.io/docs/latest/commands/hincrby/
     *
     * @example
     * $redis->hMSet('player:1', ['name' => 'Alice', 'score' => 0]);
     * $redis->hincrby('player:1', 'score', 10);
     */
    public static function hIncrBy(string $key, string $field, int $value, ?int $seconds = null, string $poolName = 'default'): \Redis|int|false|array
    {
        $instance = static::getRedis($poolName);

        if ($seconds === null) {
            return $instance->hIncrBy($key, $field, $value);
        }

        $instance->multi();
        $instance->hIncrBy($key, $field, $value);
        $instance->pexpire($key, $seconds * 1000);
        $manyResult = $instance->exec();

        return [
            'result' => data_get($manyResult, [0]),
            'pexpire' => data_get($manyResult, [1]),
        ];
    }

    /**
     * Add one or more elements and scores to a Redis sorted set.
     *
     * @param string      $key                  The sorted set in question.
     * @param array|float $score_or_options     Either the score for the first element, or an array of options.
     *                                           ```php
     *                                           $options = [
     *                                               'NX',       # Only update elements that already exist
     *                                               'NX',       # Only add new elements but don't update existing ones.
     *
     *                                               'LT'        # Only update existing elements if the new score is
     *                                                           # less than the existing one.
     *                                               'GT'        # Only update existing elements if the new score is
     *                                                           # greater than the existing one.
     *
     *                                               'CH'        # Instead of returning the number of elements added,
     *                                                           # Redis will return the number Of elements that were
     *                                                           # changed in the operation.
     *
     *                                               'INCR'      # Instead of setting each element to the provide score,
     *                                                           # increment the element by the
     *                                                           # provided score, much like ZINCRBY.  When this option
     *                                                           # is passed, you may only send a single score and member.
     *                                           ];
     *                                          ```
     *                                           Note:  'GX', 'LT', and 'NX' cannot be passed together, and PhpRedis
     *                                                  will send whichever one is last in the options array.
     * @param mixed       $more_scores_and_mems A variadic number of additional scores and members.
     *
     * @return \Redis|int|float|false|array The return value varies depending on the options passed.
     *
     * Following is information about the options that may be passed as the second argument:
     *
     * @see https://redis.io/docs/latest/commands/zadd/
     *
     * @example
     * $redis->zadd('zs', 1, 'first', 2, 'second', 3, 'third');
     * $redis->zAdd('zs', ['XX'], 8, 'second', 99, 'new-element');
     */
    public static function zAdd(string $key, array $value, ?int $seconds = null, string $poolName = 'default'): \Redis|int|float|false|array
    {
        $instance = static::getRedis($poolName);

        if ($seconds === null) {
            return $instance->zAdd($key, ...$value);
        }

        $instance->multi();
        $instance->zAdd($key, ...$value);
        $instance->pexpire($key, $seconds * 1000);
        $manyResult = $instance->exec();

        return [
            'result' => data_get($manyResult, [0]),
            'pexpire' => data_get($manyResult, [1]),
        ];
    }

    /**
     * Increment a key by a specific integer value
     *
     * @see https://redis.io/docs/latest/commands/incrby/
     *
     * @param string $key   The key to increment.
     * @param int    $value The amount to increment.
     *
     * @example
     * $redis->set('primes', 2);
     * $redis->incrby('primes', 1);
     * $redis->incrby('primes', 2);
     * $redis->incrby('primes', 2);
     * $redis->incrby('primes', 4);
     */
    public static function incrBy(string $key, int $value, ?int $seconds = null, string $poolName = 'default'): \Redis|int|false|array
    {
        $instance = static::getRedis($poolName);

        if ($seconds === null) {
            return $instance->incrBy($key, $value);
        }

        $instance->multi();
        $instance->incrBy($key, $value);
        $instance->pexpire($key, $seconds * 1000);
        $manyResult = $instance->exec();

        return [
            'result' => data_get($manyResult, [0]),
            'pexpire' => data_get($manyResult, [1]),
        ];
    }

    /**
     * Set a specific bit in a Redis string to zero or one
     *
     * @see https://redis.io/docs/latest/commands/setbit/
     *
     * @param string $key    The Redis STRING key to modify
     * @param bool   $value  Whether to set the bit to zero or one.
     *
     * @return \Redis|int|false|array The original value of the bit or false on failure.
     *
     * @example
     * $redis->set('foo', 'bar');
     * $redis->setbit('foo', 7, 1);
     */
    public static function setBit(string $key, int $idx, bool $value, ?int $seconds = null, string $poolName = 'default'): \Redis|int|false|array
    {
        $instance = static::getRedis($poolName);

        if ($seconds === null) {
            return $instance->setBit($key, $idx, $value);
        }

        $instance->multi();
        $instance->setBit($key, $idx, $value);
        $instance->pexpire($key, $seconds * 1000);
        $manyResult = $instance->exec();

        return [
            'result' => data_get($manyResult, [0]),
            'pexpire' => data_get($manyResult, [1]),
        ];
    }
}
