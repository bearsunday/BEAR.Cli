<?php

declare(strict_types=1);

namespace BEAR\Cli;

use PHPUnit\Framework\TestCase;

class CliTest extends TestCase
{
    protected Cli $cli;

    protected function setUp(): void
    {
        $this->cli = new Cli();
    }

    public function testIsInstanceOfCli(): void
    {
        $actual = $this->cli;
        $this->assertInstanceOf(Cli::class, $actual);
    }
}
