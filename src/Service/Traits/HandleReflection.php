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

use Business\Hyperf\Di\MetadataCollector;
use ReflectionException;
use ReflectionMethod;

use function Hyperf\Collection\data_get;
use function Hyperf\Collection\data_set;

trait HandleReflection
{

    /**
     * Constructs a ReflectionMethod.
     *
     * <code>
     * $reflection = new ReflectionMethod(new Example(), 'method');
     * $reflection = new ReflectionMethod(Example::class, 'method');
     * $reflection = new ReflectionMethod('Example::method');
     * </code>
     *
     * @see https://php.net/manual/en/reflectionmethod.construct.php
     * @param object|string $objectOrMethod classname, object
     *                                      (instance of the class) that contains the method or class name and
     *                                      method name delimited by ::
     * @param null|string $method name of the method if the first argument is a classname or an object
     * @return array 方法参数数据
     * @throws ReflectionException if the class or method does not exist
     */
    public static function getReflectionMethod(object|string $objectOrMethod, ?string $method = null): array
    {
        $key = (is_object($objectOrMethod) ? get_class($objectOrMethod) : $objectOrMethod) . '::' . $method;
        if (MetadataCollector::has($key)) {
            return MetadataCollector::get($key);
        }
//        var_dump(__METHOD__);

        // 通过反射获取当前方法的参数名
        $reflection = new ReflectionMethod($objectOrMethod, $method);
        $parameters = $reflection->getParameters();

        // 将参数名与实际值组合（注意：值的数量可能少于参数名）
        $result = [];
        foreach ($parameters as $index => $param) {
            $type = $param->getType();
            $typeName = $type ? (string) $type : 'mixed'; // 关键修改
            $result[$index] = [
                'name' => $param->getName(),
                'defaultValue' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : ($param->isOptional() ? null : 'REQUIRED'),
                'type' => $typeName,
            ];
        }

        MetadataCollector::set($key, $result);

        //        var_dump(__METHOD__,$result);

        return $result;
    }

    public static function getMethodParameterName($objectOrClass, $name)
    {
        // 获取参数
        $parameters = static::getReflectionMethod($objectOrClass, $name);

        return data_get($parameters, ['*', 'name']);
    }

    public static function getMethodParametersAsArray(string $service, string $method, array $args): array
    {
//        $args = array_values($args);//将 $args 转换为索引数组
        $reflectionMethod = static::getReflectionMethod($service, $method);

        // 将参数名与实际值组合（注意：值的数量可能少于参数名）
        $result = [];
        foreach ($reflectionMethod as $index => $param) {
            $name = data_get($param, ['name']);
            $defaultValue = data_get($param, ['defaultValue']);
            $type = data_get($param, ['type']);

            data_set($result, [$name], data_get($args, [$name], data_get($args, [$index], $defaultValue)));
        }

        //        var_dump(__METHOD__, $result);

        return $result;
    }
}
