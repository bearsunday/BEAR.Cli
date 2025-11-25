<?php

declare(strict_types=1);

namespace BEAR\Cli\Attribute;

use Attribute;

/** @psalm-immutable */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Option
{
    public function __construct(
        public string $shortName,
        public string $description,
    ) {
    }
}
