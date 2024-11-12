<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Resource\ResourceInterface;

/**
 * Generic CLI command for BEAR.Sunday resources
 */
final class ResourceCommand
{
    private readonly CliInvoker $invoker;

    public function __construct(
        private readonly Config $config,
        private readonly ResourceInterface $resource,
    ) {
        $this->invoker = new CliInvoker($this->resource);
    }

    public function __invoke(array $argv): CommandResult
    {
        return $this->invoker->invoke($this->config, $argv);
    }
}
