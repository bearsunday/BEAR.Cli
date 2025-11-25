<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Exception\RequiredCharacterDefaultValue;
use BEAR\Cli\Exception\ShortNameNotSingleCharacterException;

use function strlen;

/** @psalm-immutable */
final readonly class CliOption
{
    public function __construct(
        public string $name,
        public string $shortName,
        public string $description,
        public bool $isRequired,
        public mixed $defaultValue = null,
    ) {
        if (strlen($shortName) !== 1) {
            throw new ShortNameNotSingleCharacterException($shortName); // @codeCoverageIgnore
        }

        if ($isRequired && $defaultValue !== null) {
            throw new RequiredCharacterDefaultValue(); // @codeCoverageIgnore
        }
    }
}
