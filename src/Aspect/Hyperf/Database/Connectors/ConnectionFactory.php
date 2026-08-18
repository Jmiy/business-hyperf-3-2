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

namespace Business\Hyperf\Aspect\Hyperf\Database\Connectors;

use Hyperf\Context\ApplicationContext;
use Hyperf\Database\Connection;
use Hyperf\Database\Connectors\ConnectionFactory as HyperfDatabaseConnectionFactory;
use Hyperf\Database\Connectors\MySqlConnector;
use Hyperf\Database\Connectors\PostgresConnector;
use Hyperf\Database\MySqlConnection;
use Hyperf\Database\PostgresConnection;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use InvalidArgumentException;

use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Support\call;

// #[Aspect(classes: [HyperfDatabaseConnectionFactory::class . '::createConnector', HyperfDatabaseConnectionFactory::class . '::createConnection'], annotations: [])]
class ConnectionFactory extends AbstractAspect
{
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        return call([$this, 'aop_' . $proceedingJoinPoint->methodName], [$proceedingJoinPoint]);
    }

    /**
     * Create a connector instance based on the configuration.
     *
     * @return ConnectorInterface
     * @throws InvalidArgumentException
     */
    public function aop_createConnector(ProceedingJoinPoint $proceedingJoinPoint)// ,array $config
    {
        $config = data_get($proceedingJoinPoint->arguments, 'keys.config', []);

        if (! isset($config['driver'])) {
            throw new InvalidArgumentException('A driver must be specified.');
        }

        if (ApplicationContext::getContainer()->has($key = "db.connector.{$config['driver']}")) {
            return ApplicationContext::getContainer()->get($key);
        }

        switch ($config['driver']) {
            case 'mysql':
                return new MySqlConnector();
            case 'pgsql':
                return new PostgresConnector();
        }

        throw new InvalidArgumentException("Unsupported driver [{$config['driver']}]");
    }

    /**
     * Create a new connection instance.
     *
     * @return Connection
     * @throws InvalidArgumentException
     */
    public function aop_createConnection(ProceedingJoinPoint $proceedingJoinPoint)// $driver, $connection, $database, $prefix = '', array $config = []
    {
        $driver = data_get($proceedingJoinPoint->arguments, 'keys.driver');
        $connection = data_get($proceedingJoinPoint->arguments, 'keys.connection');
        $database = data_get($proceedingJoinPoint->arguments, 'keys.database');
        $prefix = data_get($proceedingJoinPoint->arguments, 'keys.prefix', '');
        $config = data_get($proceedingJoinPoint->arguments, 'keys.config', []);

        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $prefix, $config);
        }

        switch ($driver) {
            case 'mysql':
                return new MySqlConnection($connection, $database, $prefix, $config);
            case 'pgsql':
                return new PostgresConnection($connection, $database, $prefix, $config);
        }

        throw new InvalidArgumentException("Unsupported driver [{$driver}]");
    }
}
