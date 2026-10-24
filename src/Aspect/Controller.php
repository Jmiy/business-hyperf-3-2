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

namespace Business\Hyperf\Aspect;

use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Di\MetadataCollector;
use Business\Hyperf\Exception\Handler\AppExceptionHandler;
use Business\Hyperf\Utils\Response;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\ReflectionManager;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Controller as AnnotationController;

use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PatchMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Annotation\RequestMapping;

use Hyperf\Logger\LoggerFactory;
use function Hyperf\Collection\data_get;

#[Aspect(classes: [AppExceptionHandler::class . '::handle'], annotations: [AnnotationController::class, AutoController::class])]
class Controller extends AbstractAspect
{
    public function isAop(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $className = $proceedingJoinPoint->className;//当前被调用的类
        $methodName = $proceedingJoinPoint->methodName;//当前被调用的函数
//        $arguments = $proceedingJoinPoint->arguments;//当前被调用的函数参数

//        ApplicationContext::getContainer()->get(LoggerFactory::class)->get('sys', 'sys')->info(
//            __METHOD__,
//            [
//                'className' => $className,
//                'methodName' => $methodName,
//            ],
//        );

        $key = 'aop::response::' . $className . '::' . $methodName;
        if (MetadataCollector::has($key)) {
            return MetadataCollector::get($key);
        }

//        $classAnnotations = $proceedingJoinPoint->getAnnotationMetadata()->class;//当前被调用的类的注解
//        $methodAnnotations = $proceedingJoinPoint->getAnnotationMetadata()->method;//当前被调用的函数的注解
        $metadata = AnnotationCollector::get($className);
        $classAnnotations = $metadata['_c'] ?? [];//当前被调用的类的注解
        $methodAnnotations = $metadata['_m'][$methodName] ?? [];//当前被调用的函数的注解
//        var_dump(
//            __METHOD__,
//            [
//                'className' => $className,
//                'methodName' => $methodName,
//                'classAnnotations' => $classAnnotations,
//                'methodAnnotations' => $methodAnnotations,
//            ],
//        );
//
//        ApplicationContext::getContainer()->get(LoggerFactory::class)->get('sys', 'sys')->info(
//            __METHOD__,
//            [
//                'className' => $className,
//                'methodName' => $methodName,
//                'classAnnotations' => $classAnnotations,
//                'methodAnnotations' => $methodAnnotations,
//            ],
//        );

        $result = [
            'aop' => true,
            'annotation' => null,
        ];

        // AnnotationController::class和AutoController::class 不可以同时使用在同一个类上面
        if (isset($classAnnotations[AutoController::class])) {
            $result['aop'] = $proceedingJoinPoint->getReflectMethod()->isPublic();
        } else if (isset($classAnnotations[AnnotationController::class])) {
            $result['aop'] = false;
        }

        if(isset($classAnnotations[AutoController::class]) || isset($classAnnotations[AnnotationController::class])){
            $mappingAnnotationClasses = [
                RequestMapping::class,
                GetMapping::class,
                PostMapping::class,
                PutMapping::class,
                PatchMapping::class,
                DeleteMapping::class,
            ];

            $methodAnnotationClasses = array_keys($methodAnnotations);
            foreach ($methodAnnotationClasses as $methodAnnotationClass) {
                if (in_array($methodAnnotationClass, $mappingAnnotationClasses)) {
                    $result['annotation'] = data_get($methodAnnotations, [$methodAnnotationClass]);
                    $result['aop'] = data_get($result, ['annotation', 'options', 'aop'], true);
                    break;
                }
            }
        }

        MetadataCollector::set($key, $result);
        return $result;
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        // 切面切入后，执行对应的方法会由此来负责
        // $proceedingJoinPoint 为连接点，通过该类的 process() 方法调用原方法并获得结果
        // 在调用前进行某些处理
        $result = $proceedingJoinPoint->process();

        $aopResult = $this->isAop($proceedingJoinPoint);
        $annotation = data_get($aopResult, ['annotation']);
        if (data_get($aopResult, ['aop']) === false) {
            return $result;
        }

        $isNeedDataKey = data_get($annotation, ['options', 'isNeedDataKey'], true);
        $responseStatusCode = data_get($result, [Constant::RESPONSE_STATUS_CODE]);
        if ($responseStatusCode !== null) {
            unset($result[Constant::RESPONSE_STATUS_CODE]);
        }
        $responseStatusCode = $responseStatusCode ?? Constant::CODE_SUCCESS;

        $responseHeaders = data_get($result, [Constant::RESPONSE_HEADERS]);
        if ($responseHeaders !== null) {
            unset($result[Constant::RESPONSE_HEADERS]);
        }
        $responseHeaders = $responseHeaders ?? [];

        return Response::json(...Response::getResponseData($result, $isNeedDataKey, $responseStatusCode, $responseHeaders));
    }
}
