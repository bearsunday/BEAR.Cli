<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Exception\RequiredOptionWithDefaultValueException;
use BEAR\Cli\Exception\ShortNameNotSingleCharacterException;

use function sprintf;
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
            throw new ShortNameNotSingleCharacterException(sprintf('Short name must be a single character, got: %s', $shortName)); // @codeCoverageIgnore
        }

        if ($isRequired && $defaultValue !== null) {
            throw new RequiredOptionWithDefaultValueException('Required options cannot have a default value'); // @codeCoverageIgnore
        }
    }
}
