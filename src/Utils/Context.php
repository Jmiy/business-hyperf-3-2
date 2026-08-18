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

namespace Business\Hyperf\Utils;

use Hyperf\Context\Context as HyperfContext;

use function Hyperf\Support\call;

class Context extends HyperfContext
{
    // protected static $nonCoContext = [];

    public static function storeData($key, callable $callback)
    {
        if (! static::has($key)) {
            return static::set($key, call($callback));
        }

        return static::get($key);
    }
}
