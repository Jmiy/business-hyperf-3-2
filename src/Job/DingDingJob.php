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

namespace Business\Hyperf\Job;

use Business\Hyperf\Constants\Constant;
use Business\Hyperf\Service\Log\LogService;
use Business\Hyperf\Utils\Support\Facades\QueueRedisDriver;
use Business\Hyperf\Utils\Support\Facades\Redis;
use Throwable;

use function Business\Hyperf\Utils\Collection\data_get;
use function Hyperf\Config\config;
use function Hyperf\Coroutine\go;

class DingDingJob extends Job
{
    /**
     * @var string
     */
    protected $robot = 'default';

    private $message;

    private $code;

    private $file;

    private $line;

    private $url;

    private $trace;

    private $exception;

    private $simple;

    /**
     * Create a new job instance.
     *
     * @param mixed $url
     * @param mixed $exception
     * @param mixed $message
     * @param mixed $code
     * @param mixed $file
     * @param mixed $line
     * @param mixed $trace
     * @param mixed $robot
     * @param mixed $simple
     */
    public function __construct($url, $exception, $message, $code, $file, $line, $trace, $robot = 'default', $simple = false)
    {
        $this->message = $message;
        $this->code = $code;
        $this->file = $file;
        $this->line = $line;
        $this->url = $url;
        $this->trace = $trace;
        $this->exception = $exception;
        $this->robot = $robot;
        $this->simple = $simple;
    }

    /**
     * Execute the job.
     * ding()->at([],true)->text(implode(PHP_EOL, $message));//@所有人.
     */
    public function handle()
    {
        $messages = [];
        foreach ($this->trace as $key => $value) {
            if ($key == 'break') {
                break;
            }
            $messages[] = implode(':', [$key, $value]);
        }

        if ($this->code && ! $this->simple) {
            $messages[] = implode(':', ['stackTrace', is_array($this->trace) ? json_encode($this->trace, JSON_UNESCAPED_UNICODE) : $this->trace]);
        }

        $data = [
            'code' => $this->code,
            'message' => $this->message,
            'file' => $this->file,
            'line' => $this->line,
            'business_data' => json_encode($this->trace, JSON_UNESCAPED_UNICODE),
            'stack_trace' => data_get($this->trace, 'stackTrace', ''),
            'server_ip' => data_get($this->trace, ['serverIp'], ''), // 服务器ip
            'level' => data_get($this->trace, 'level', ''),
            'client_ip' => data_get($this->trace, 'clientIp', ''),
        ];

        $isInsertDb = config('monitor.isInsertDb', true); // 是否记录到数据库 true：是  false：否  默认：true
        if ($isInsertDb) {
            LogService::insertData('Log', [data_get($this->trace, Constant::DB_COLUMN_PLATFORM, ''), date('Ymd')], $data);
        }

        if (config('ding.' . $this->robot . '-' . $this->code)) {
            $this->robot = $this->robot . '-' . $this->code;
        }
        $dingConfig = config('ding.' . $this->robot, []);

        $dingCodeData = explode(',', data_get($dingConfig, ['code'], ''));
        if (in_array('all', $dingCodeData) || in_array($this->code, $dingCodeData)) {
            $nx = true;
            $poolName = data_get($dingConfig, ['poolName'], 'default');
            $ex = data_get($dingConfig, ['lockEx'], 3600);
            unset($data['business_data'], $data['stack_trace'], $data['client_ip']);
            $lockKeys = [md5(json_encode($data, JSON_UNESCAPED_UNICODE))];

            $distributedLockKey = QueueRedisDriver::getKey(
                ['ding'],
                [$this->code],
                $lockKeys
            );
            try {
                $redis = Redis::getRedis($poolName);

                // 获取分布式锁
                $nx = $redis->set($distributedLockKey, 1, ['nx', 'ex' => $ex]); // Will set the key, if it doesn't exist, with a ttl of 10 seconds
                //            //释放分布式锁
                //            $rs = $redis->del($distributedLockKey);
            } catch (Throwable $exception) {
                //                go(function () use ($throwable) {
                //                    throw $throwable;
                //                });
            }

            if ($nx == true) {
                ding()->with($this->robot)->text(implode(PHP_EOL, $messages));
            }
        }
    }
}
