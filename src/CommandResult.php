<?php

declare(strict_types=1);

namespace BEAR\Cli;

/** @psalm-immutable */
final readonly class CommandResult
{
    public function __construct(
        public string $message,
        public int $exitCode = 0,
    ) {
    }
}
