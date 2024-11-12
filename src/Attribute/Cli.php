<?php

declare(strict_types=1);

namespace BEAR\Cli\Attribute;

use Attribute;

/** @psalm-immutable */
#[Attribute(Attribute::TARGET_METHOD)]
final class Cli
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $output = '',
        public readonly string $version = '0.1.0',
    ) {
    }
}
