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

namespace Business\Hyperf\Service\Log;

use Business\Hyperf\Model\Log\Log;
use Business\Hyperf\Service\BaseService;

class LogService extends BaseService
{
    /**
     * 获取模型别名.
     * @return string
     */
    public static function getModelAlias()
    {
        return Log::class;
    }
}
