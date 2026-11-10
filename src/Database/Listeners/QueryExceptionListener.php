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

namespace Business\Hyperf\Database\Listeners;

use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Database\Events\QueryExceptionEvent;
use Business\Hyperf\Service\Traits\BaseDb;
use Business\Hyperf\Service\Traits\HandleCache;
use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function Hyperf\Config\config;

#[Listener]
class QueryExceptionListener implements ListenerInterface
{
    use HandleCache;
    use BaseDb;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerFactory::class)->get('sql', 'sql');
    }

    public function listen(): array
    {
        return [
            QueryExceptionEvent::class,
        ];
    }

    /**
     * @param QueryExecuted $event
     */
    public function process(object $event): void
    {
        if ($event instanceof QueryExceptionEvent) {
            $throwable = $event->throwable;
            $builder = $event->builder;
            $throwableMessage = $throwable->getMessage();

            // 数据表不存在
            if (stripos($throwableMessage, 'Base table or view not found') !== false && stripos($throwableMessage, "doesn't exist") !== false) {
                $model = $builder->getModel();
                if (! empty($model::TABLE_PREFIX)) {
                    $connection = $model->getConnectionName();
                    $tableTemplate = config(Constant::DATABASES . Constant::LINKER . $connection . Constant::LINKER . 'table_template' . Constant::LINKER . $model::TABLE_PREFIX);
                    $table = $model->getTable();

                    if (! empty($tableTemplate)) {
                        static::clearCreateTableMarker($connection, $table);
                    }
                }
            }
        }
    }
}
