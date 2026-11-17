<?php

declare(strict_types=1);

namespace BEAR\Cli\Attribute;

use Attribute;

/**
 * Exposes the parameter as the CLI option `--<parameter name>`
 *
 * @see https://bearsunday.github.io/manuals/1.0/en/cli.html#adding-cli-attributes-to-resources
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Option
{
    /**
     * @param string $shortName Single letter of the short option `-<letter>`;
     *                          any other length throws ShortNameNotSingleCharacterException
     */
    public function __construct(
        public string $shortName,
        public string $description,
    ) {
    }
}
