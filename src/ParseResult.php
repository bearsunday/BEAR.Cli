<?php

declare(strict_types=1);

namespace BEAR\Cli;

/** @psalm-immutable */
final readonly class ParseResult
{
    public function __construct(
        public string $name,
        public string|bool $value,
        public int $increment,
    ) {
    }
}
