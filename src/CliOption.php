<?php

declare(strict_types=1);

namespace BEAR\Cli;

/** @psalm-immutable */
final class CliOption
{
    public function __construct(
        public readonly string $name,
        public readonly string $shortName,
        public readonly string $description,
        public readonly string $type,
        public readonly bool $isRequired,
        public readonly mixed $defaultValue = null,
    ) {
    }
}
