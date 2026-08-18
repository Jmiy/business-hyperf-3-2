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
use Business\Hyperf\Model\BaseModel;
use Hyperf\Database\Model\Relations\Relation;

use function Business\Hyperf\Utils\Collection\data_get;

trait GetDefaultConnectionModel
{
    /**
     * 获取模型 model.
     * @param null|array|string $connection 数据库连接 强制使用model配置的connection
     * @param null|array|string $table 表名 默认使用model配置的表名
     * @param null|array $parameters model初始化参数
     * @param null|string $make model别名 默认:null
     * @param null|Relation $relation 关联对象
     * @param null|array $dbConfig 数据库配置
     * @return null|BaseModel|Relation|string
     */
    public static function getModel(array|string $connection = Constant::DB_CONNECTION_DEFAULT, null|array|string $table = null, ?array $parameters = [], ?string $make = null, ?Relation &$relation = null, ?array $dbConfig = [])
    {
        $baseConfig = static::handleDbConfig($connection, $table);
        $connection = data_get($baseConfig, Constant::CONNECTION);
        $table = data_get($baseConfig, Constant::DB_EXECUTION_PLAN_TABLE);

        // data_set($parameters, 'attributes.storeId', $connection, false); //设置 model attributes.storeId
        return BaseModel::createModel(Constant::DB_EXECUTION_PLAN_DEFAULT_CONNECTION . $table, static::getMake($make), $parameters, $table, $relation, $dbConfig);
    }
}
