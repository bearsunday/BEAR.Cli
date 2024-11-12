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
        public readonly bool $isRequired,
        public readonly mixed $defaultValue = null,
    ) {
        if (strlen($shortName) !== 1) {
            throw new \InvalidArgumentException('Short name must be a single character');
        }

        if ($isRequired && $defaultValue !== null) {
            throw new \InvalidArgumentException('Required options cannot have a default value');
        }
    }
}
