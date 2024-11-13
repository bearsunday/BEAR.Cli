<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Fake\FakeErrorResource;
use BEAR\Cli\Fake\FakeResource;
use BEAR\Cli\Fake\FakeResourceFactory;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
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

    public function testInvokeWithShortOptionsAmdLongOptions(): void
    {
        $result = ($this->command)(['greeting', '--name', 'BEAR', '-n', 'Sunday']);
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

    public function testInvokeDefaultJsonFormatWhenNoOutput(): void
    {
        $config = new Config('app://self/greeting', new ReflectionMethod(FakeResource::class, 'onPost'));
        $command = new ResourceCommand($config, $this->resource);
        $result = $command(['post-greeting']);
        $this->assertJson($result->message);
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

    /** @return array<string, array<int>> */
    public static function statusCodeProvider(): array
    {
        return [
            'success' => [200, 0],
            'client error' => [400, 1],
            'not found' => [404, 1],
            'server error' => [500, 2],
        ];
    }

    public function testInvokeWithNonStringOutput(): void
    {
        $mockResource = new FakeExceptionResource();

        $command = new ResourceCommand($this->config, $mockResource);
        $result = $command(['greeting', '--name', 'BEAR']);

        $this->assertJson($result->message);
        $decoded = json_decode($result->message, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('output', $decoded);
    }

    public function testInvokeWithRuntimeException(): void
    {
        $mockResource = new FakeStub2Resource();
        $command = new ResourceCommand($this->config, $mockResource);
        $result = $command(['greeting', '--name', 'BEAR']);

        $this->assertSame(2, $result->exitCode);
        $this->assertStringContainsString('Runtime error occurred', $result->message);
    }

    public function testInvokeWithGeneralException(): void
    {
        $resource = new FakeStubResource();
        $command = new ResourceCommand($this->config, $resource);
        $result = $command(['greeting', '--name', 'BEAR']);

        $this->assertSame(2, $result->exitCode);
        $this->assertStringContainsString('Exception', $result->message);
        $this->assertStringContainsString('Unexpected error', $result->message);
    }
}

class FakeNonStringOutputResource extends ResourceObject
{
}
