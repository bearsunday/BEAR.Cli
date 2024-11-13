<?php

namespace BEAR\Cli;

/**
 * @psalm-immutable
 */
final readonly class ParseResult
{
    public function __construct(
        public string $name,
        public string|bool $value,
        public int $increment,
    ) {
    }
}
