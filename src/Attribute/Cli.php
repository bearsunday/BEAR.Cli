<?php

declare(strict_types=1);

namespace BEAR\Cli\Attribute;

use Attribute;

/**
 * Generates a CLI command from the resource method
 *
 * @see https://bearsunday.github.io/manuals/1.0/en/cli.html#adding-cli-attributes-to-resources
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Cli
{
    /** @param string $output Body key printed by default; without it in the body, the whole body is printed as JSON */
    public function __construct(
        public string $name,
        public string $description,
        public string $output = '',
        public string $version = '0.1.0',
    ) {
    }
}
