<?php

declare(strict_types=1);

namespace BEAR\Cli\Attribute;

use Attribute;

/** @psalm-immutable */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class Option
{
    public function __construct(
        public readonly string $shortName,
        public readonly string $description,
    ) {
    }
}
