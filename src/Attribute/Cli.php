<?php

declare(strict_types=1);

namespace BEAR\Cli\Attribute;

use Attribute;

/** @psalm-immutable */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Cli
{
    public function __construct(
        public string $name,
        public string $description,
        public string $output = '',
        public string $version = '0.1.0',
    ) {
    }
}
