<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Fake\FakeErrorResource;
use BEAR\Cli\Fake\FakeResource;
use BEAR\Cli\Fake\FakeResourceFactory;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function json_decode;

class ResourceCommandTest extends TestCase
{
    private ResourceInterface $resource;
    private Config $config;
    private ResourceCommand $command;

    protected function setUp(): void
    {
        $this->resource = new FakeResourceFactory();
        $this->config = new Config('app://self/greeting', new ReflectionMethod(FakeResource::class, 'onGet'));
        $this->command = new ResourceCommand($this->config, $this->resource);
    }

    public function testInvokeHelp(): void
    {
        $result = ($this->command)(['greeting', '--help']);

        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('Say hello in multiple languages', $result->message);
        $this->assertStringContainsString('--name, -n', $result->message);
        $this->assertStringContainsString('--lang, -l', $result->message);
    }

    public function testInvokeVersion(): void
    {
        $result = ($this->command)(['greeting', '--version']);

        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('greeting version 0.1.0', $result->message);
    }

    public function testInvokeWithRequiredOption(): void
    {
        $result = ($this->command)(['greeting', '--name', 'BEAR']);

        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('Hello, BEAR', $result->message);
    }

    public function testInvokeWithOptionalOption(): void
    {
        $result = ($this->command)(['greeting', '--name', 'BEAR', '--lang', 'ja']);

        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('こんにちは, BEAR', $result->message);
    }

    public function testInvokeWithJsonFormat(): void
    {
        $result = ($this->command)(['greeting', '--name', 'BEAR', '--format', 'json']);

        $this->assertSame(0, $result->exitCode);
        $json = json_decode($result->message, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('greeting', $json);
        $this->assertArrayHasKey('lang', $json);
    }

    public function testInvokeMissingRequiredOption(): void
    {
        $result = ($this->command)(['greeting']);

        $this->assertSame(1, $result->exitCode);
        $this->assertStringContainsString('Option --name is required', $result->message);
    }

    public function testInvokeWithInvalidOption(): void
    {
        $result = ($this->command)(['greeting', '--invalid-option']);

        $this->assertSame(1, $result->exitCode);
        $this->assertStringContainsString('Error: Option', $result->message);
    }

    /** @dataProvider statusCodeProvider */
    public function testInvokeWithStatusCode(int $statusCode, int $expectedExitCode): void
    {
        $method = new ReflectionMethod(FakeErrorResource::class, 'onGet');
        $command = new ResourceCommand(new Config('app://self/error', $method), $this->resource);
        $result = $command(['error', '--code', (string) $statusCode]);

        $this->assertSame($expectedExitCode, $result->exitCode);
    }

    /** @return list<array{0:int, 1:int}> */
    public function statusCodeProvider(): array
    {
        return [
            'success' => [200, 0],
            'client error' => [400, 1],
            'not found' => [404, 1],
            'server error' => [500, 2],
        ];
    }
}
