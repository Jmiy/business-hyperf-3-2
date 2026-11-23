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

namespace Business\Hyperf\Listener;

use Hyperf\Collection\Arr;
use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function Hyperf\Collection\data_get;
use function Hyperf\Config\config;

#[Listener]
class DbQueryExecutedListener implements ListenerInterface
{
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
            QueryExecuted::class,
        ];
    }

    public function getRawQueryLog($connection, $queryLog = [])
    {
        return array_map(fn (array $log) => [
            'raw_query' => $connection->getQueryGrammar()->substituteBindingsIntoRawSql(
                $log['query'],
                array_map(fn ($value) => $connection->escape($value), $connection->prepareBindings($log['bindings']))
            ),
            'time' => $log['time'],
        ], $queryLog);
    }

    /**
     * @param QueryExecuted $event
     */
    public function process(object $event): void
    {
        if (in_array(config('app_env', 'dev'), ['release', 'prod'])) {
            return;
        }

        if ($event instanceof QueryExecuted) {
            $rawQueryLog = $this->getRawQueryLog($event->connection, [
                [
                    'query' => $event->sql,
                    'bindings' => $event->bindings,
                    'time' => $event->time,
                ],
            ]);
            $sql = data_get($rawQueryLog, [0, 'raw_query']) ?? '';

            $this->logger->info(sprintf('[%s ms databases.pool: %s] %s', $event->time, $event->connectionName, $sql));
        }
    }
}
