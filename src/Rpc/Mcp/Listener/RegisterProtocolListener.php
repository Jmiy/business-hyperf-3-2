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

namespace Business\Hyperf\Rpc\Mcp\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Rpc\ProtocolManager;
use Hyperf\Framework\Event\BootApplication;
use Business\Hyperf\Rpc\Mcp\PathGenerator\PathGenerator;
use Business\Hyperf\Rpc\Mcp\DataFormatter;
use Hyperf\Codec\Packer\JsonPacker;
use Hyperf\JsonRpc\JsonRpcHttpTransporter;
use Hyperf\JsonRpc\JsonRpcNormalizer;

class RegisterProtocolListener implements ListenerInterface
{
    public function __construct(private ProtocolManager $protocolManager)
    {
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    /**
     * All official rpc protocols should register in here,
     * and the others non-official protocols should register in their own component via listener.
     */
    public function process(object $event): void
    {
        $this->protocolManager->register('mcp-jsonrpc-http', [
            'path-generator' => PathGenerator::class,
            'data-formatter' => DataFormatter::class,
            'packer' => JsonPacker::class,
            'transporter' => JsonRpcHttpTransporter::class,
            'normalizer' => JsonRpcNormalizer::class,
        ]);
    }
}
