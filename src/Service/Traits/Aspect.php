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

use function Business\Hyperf\Utils\Collection\data_get;

trait Aspect
{
    /**
     * 切面配置方法 此方法禁止使用 Business\Hyperf\Annotation\Service 否则会导致死循环.
     * @param string $methodName 被aop的方法
     * @return array|mixed
     */
    public static function aspect(string $methodName)
    {
        $aspectMap = [
            //            'updateRoleDataPermission' => [
            //                //'before' => getJobData(static::class, 'clearCacheService', [], null, ['methodName' => $methodName]),
            //                'after' => getJobData(static::class, 'clearCacheService', [], null, ['methodName' => $methodName]),
            //            ],
        ];
        return data_get($aspectMap, $methodName);
    }
}
