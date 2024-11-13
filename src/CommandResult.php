<?php

declare(strict_types=1);

namespace BEAR\Cli;

/** @psalm-immutable */
final class CommandResult
{
    public function __construct(
        public readonly string $message,
        public readonly int $exitCode = 0,
    ) {
    }
}
