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

use function Business\Hyperf\Utils\Collection\data_get;

trait ExistsFirst
{
    /**
     * 检查是否存在.
     * @param $where where条件
     * @param $getData 是否返回数据
     * @param $select 查询的字段
     * @param $orders 排序
     * @param array|string $connection 数据库连接
     * @param null|array|string $table 数据表
     */
    public static function existsOrFirst(
        $where = [],
        $getData = false,
        $select = null,
        $orders = [],
        array|string $connection = Constant::DB_CONNECTION_DEFAULT,
        null|array|string $table = null
    ): mixed {
        if (empty($where)) {
            return $getData ? [] : true;
        }

        $query = static::getModel($connection, $table)->buildWhere($where);

        if ($orders) {
            foreach ($orders as $order) {
                $query->orderBy(data_get($order, 0), data_get($order, 1));
            }
        }

        if ($getData) {
            if ($select !== null) {
                $query = $query->select($select);
            }
            $rs = $query->first();
        } else {
            $rs = $query->count();
        }

        return $rs;
    }
}
