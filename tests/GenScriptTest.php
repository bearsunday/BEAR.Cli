<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Fake\FakeResource;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class GenScriptTest extends TestCase
{
    private GenScript $genScript;

    protected function setUp(): void
    {
        $this->genScript = new GenScript();
    }

    public function testInvoke(): void
    {
        $method = new ReflectionMethod(FakeResource::class, 'onGet');
        $source = ($this->genScript)('app://self/fake', '/path/to/app', $method);

        $this->assertSame('greeting', $source->name);
        $this->assertStringContainsString(
            "new Config('app://self/fake', new \ReflectionMethod(\BEAR\Cli\Fake\FakeResource::class, 'onGet')",
            $source->code,
        );
        $this->assertStringContainsString(
            'use BEAR\Package\Injector;',
            $source->code,
        );
        $this->assertStringContainsString(
            '$resource = Injector::getInstance(\'BEAR\Cli\', \'prod-app\', \'/path/to/app\')->getInstance(ResourceInterface::class);',
            $source->code,
        );
        $this->assertStringContainsString(
            'exit($result->exitCode);',
            $source->code,
        );
    }

    public function testInvokeWithPostMethod(): void
    {
        $method = new ReflectionMethod(FakeResource::class, 'onPost');
        $source = ($this->genScript)('FakeVendor\FakeProject', 'app://self/fake', $method);

        $this->assertSame('post-greeting', $source->name);
        $this->assertStringContainsString('onPost', $source->code);
    }

    public function testInvokeWithNoCliAttribute(): void
    {
        $this->expectException(Exception\LogicException::class);
        $this->expectExceptionMessage('No CLI attribute found');

        $method = new ReflectionMethod(FakeResource::class, 'noCliMethod');
        ($this->genScript)('FakeVendor\FakeProject', 'app://self/fake', $method);
    }
}
