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

namespace Business\Hyperf\Database\Events;

use Hyperf\Database\Model\Builder;
use Throwable;

class QueryExceptionEvent
{
    /**
     * Create a new event instance.
     * @param Builder $builder 模型构建者
     * @param null|Throwable $throwable 异常
     */
    public function __construct(public Builder $builder, public ?Throwable $throwable = null)
    {
    }
}
