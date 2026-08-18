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

namespace Business\Hyperf\Aspect\Hyperf\Coroutine;

use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Exception\Handler\AppExceptionHandler;
use Hyperf\Context\ApplicationContext;
use Hyperf\Context\Context;
use Hyperf\Coroutine\Coroutine as CoroutineCoroutine;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Engine\Coroutine as Co;
use Throwable;

use function Hyperf\Collection\data_get;
use function Hyperf\Config\config;
use function Hyperf\Support\call;
use function Hyperf\Support\make;

#[Aspect(classes: [CoroutineCoroutine::class . '::create', CoroutineCoroutine::class . '::printLog'], annotations: [])]
class Coroutine extends AbstractAspect
{
    public function create(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $arguments = $proceedingJoinPoint->arguments;
        $callable = data_get($arguments, ['keys', 'callable'], []);
        $id = CoroutineCoroutine::id();

        $coroutine = Co::create(static function () use ($callable, $id) {
            try {
                // 按需复制，禁止复制 Socket，不然会导致 Socket 跨协程调用从而报错。
                $keys = config('common.context_copy', []);
                $keys[] = Constant::JSON_RPC_HEADERS_KEY; // 'json-rpc-headers';
                Context::copy($id, $keys);
                call($callable);
            } catch (Throwable $throwable) {
                try {
                    ApplicationContext::getContainer()->get(AppExceptionHandler::class)->log($throwable);
                } catch (Throwable $e1) {
                }
            }
        });

        try {
            return $coroutine->getId();
        } catch (Throwable) {
            return -1;
        }
    }

    public function printLog(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $throwable = $proceedingJoinPoint->getArguments();
        try {
            ApplicationContext::getContainer()->get(AppExceptionHandler::class)->log(...$throwable);
        } catch (Throwable $e1) {
        }
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        return call([$this, $proceedingJoinPoint->methodName], [$proceedingJoinPoint]);
    }
}
