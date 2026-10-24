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

namespace Hyperf\RateLimit\Aspect;

use bandwidthThrottle\tokenBucket\storage\StorageException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Di\Aop\AroundInterface;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\RateLimit\Annotation\RateLimit;
use Hyperf\RateLimit\Exception\RateLimitException;
use Hyperf\RateLimit\Handler\RateLimitHandler;

use function Hyperf\Tappable\tap;
use function Hyperf\Collection\data_get;
use function Hyperf\Config\config;
use function Hyperf\Support\make;

class RateLimitAnnotationAspect implements AroundInterface
{
    public array $classes = [];

    public array $annotations = [
        RateLimit::class,
    ];

    private array $annotationProperty;

    private array $config;

    public function __construct(ConfigInterface $config, private RequestInterface $request, private RateLimitHandler $rateLimitHandler)
    {
        $this->annotationProperty = get_object_vars(new RateLimit());
        $this->config = $this->parseConfig($config);
    }

    /**
     * @throws RateLimitException limit but without handle
     * @throws StorageException when the storage driver bootstrap failed
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $annotation = $this->getWeightingAnnotation($this->getAnnotations($proceedingJoinPoint));

        $bucketKey = $annotation->key;
        if (is_callable($bucketKey)) {
            $bucketKey = $bucketKey($proceedingJoinPoint);
        }
        if (! $bucketKey) {
            $bucketKey = $this->request->getUri()->getPath();
        }

        $bucket = $this->rateLimitHandler->build($bucketKey, $annotation->create, $annotation->capacity, $annotation->waitTimeout);

        $maxTime = microtime(true) + $annotation->waitTimeout;
        $seconds = 0;

        while (true) {
            try {
                if ($bucket->consume($annotation->consume ?? 1, $seconds)) {
                    return $proceedingJoinPoint->process();
                }
            } catch (StorageException) {
            }
            if (microtime(true) + $seconds > $maxTime) {
                break;
            }
            Coroutine::sleep(max($seconds, 0.001));
        }

        if (! $annotation->limitCallback || ! is_callable($annotation->limitCallback)) {
            throw new RateLimitException('Service Unavailable.', 503);
        }
        return call_user_func($annotation->limitCallback, $seconds, $proceedingJoinPoint);
    }

    /**
     * @param RateLimit[] $annotations
     */
    public function getWeightingAnnotation(array $annotations): RateLimit
    {
        $property = $this->annotationProperty;
        foreach ($this->annotationProperty as $key => $value) {
            if (! empty($this->config[$key])) {
                $property[$key] = $this->config[$key];
            }
        }

        /** @var null|RateLimit $annotation */
        foreach ($annotations as $annotation) {
            if (! $annotation) {
                continue;
            }
            $property = array_merge($property, array_filter(get_object_vars($annotation)));
        }
        return tap(new RateLimit(), static function (RateLimit $rateLimit) use ($property) {
            foreach ($property as $key => $value) {
                $rateLimit->{$key} = $value;
            }
        });
    }

    /**
     * AOP update jmiy 2026-10-24
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return array
     */
    public function getAnnotations(ProceedingJoinPoint $proceedingJoinPoint): array
    {
        /* AOP handle start */
        $rateLimit = null;
        $rateLimitConfig = data_get($proceedingJoinPoint->arguments, ['keys', 'extendData', 'rate_limit'],data_get($proceedingJoinPoint->arguments, ['keys', 'context', 'rate_limit']));
        if ($rateLimitConfig !== null) {
            $rateLimitConfig = is_array($rateLimitConfig) ? $rateLimitConfig : config($rateLimitConfig);
            if ($rateLimitConfig) {
                $annotationProperty = $this->annotationProperty;
                foreach ($this->annotationProperty as $key => $value) {
                    if (isset($rateLimitConfig[$key]) && ! empty($rateLimitConfig[$key])) {
                        $annotationProperty[$key] = $rateLimitConfig[$key];
                    }
                }
                $rateLimit = make(RateLimit::class, $annotationProperty);
            }
        }
        /* AOP handle end */

        $metadata = $proceedingJoinPoint->getAnnotationMetadata();
        return [
            $metadata->class[RateLimit::class] ?? null,
            $metadata->method[RateLimit::class] ?? null,
            $rateLimit,
        ];
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    protected function parseConfig(ConfigInterface $config): array
    {
        if ($config->has('rate_limit')) {
            return $config->get('rate_limit', []);
        }

        return [
            'create' => 1,
            'consume' => 1,
            'capacity' => 2,
            'limitCallback' => [],
            'waitTimeout' => 1,
        ];
    }
}
