<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Exception\RequiredCharacterDefaultValue;
use BEAR\Cli\Exception\ShortNameNotSingleCharacterException;

use function strlen;

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
            throw new ShortNameNotSingleCharacterException($shortName); // @codeCoverageIgnore
        }

        if ($isRequired && $defaultValue !== null) {
            throw new RequiredCharacterDefaultValue(); // @codeCoverageIgnore
        }
    }
}
