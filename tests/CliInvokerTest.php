<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Fake\FakeResourceFactory;
use PHPUnit\Framework\TestCase;

use function json_decode;

class CliInvokerTest extends TestCase
{
    private CliInvoker $invoker;
    private Config $config;

    protected function setUp(): void
    {
        $resource = new FakeResourceFactory();
        $this->config = new Config(
            name: 'greet',
            description: 'Say hello in multiple languages',
            version: '1.0.0',
            method: 'get',
            uri: 'app://self/greeting',
            options: [
                'name' => new CliOption(
                    name: 'name',
                    shortName: 'n',
                    description: 'Name to greet',
                    type: 'string',
                    isRequired: true,
                ),
                'lang' => new CliOption(
                    name: 'lang',
                    shortName: 'l',
                    description: 'Language',
                    type: 'string',
                    isRequired: false,
                    defaultValue: 'en',
                ),
            ],
            output: 'greeting',
        );

        $this->invoker = new CliInvoker($resource);
    }

    public function testInvokeHelp(): void
    {
        $result = $this->invoker->invoke($this->config, ['greet', '--help']);
        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('Say hello in multiple languages', $result->message);
        $this->assertStringContainsString('--name, -n', $result->message);
        $this->assertStringContainsString('--lang, -l', $result->message);
    }

    public function testInvokeVersion(): void
    {
        $result = $this->invoker->invoke($this->config, ['greet', '--version']);
        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('greet version 1.0.0', $result->message);
    }

    public function testInvokeSuccess(): void
    {
        $result = $this->invoker->invoke($this->config, ['greet', '--name=World', '--lang=ja']);

        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('こんにちは, World', $result->message);
    }

    public function testInvokeWithDefaultLanguage(): void
    {
        $result = $this->invoker->invoke($this->config, ['greet', '--name=World']);
        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('Hello, World', $result->message);
    }

    public function testInvokeWithJsonFormat(): void
    {
        $result = $this->invoker->invoke($this->config, ['greet', '--name=World', '--format=json']);
        $this->assertSame(0, $result->exitCode);
        $decoded = json_decode($result->message, true);
        $this->assertArrayHasKey('greeting', $decoded);
        $this->assertArrayHasKey('timestamp', $decoded);
        $this->assertArrayHasKey('lang', $decoded);
    }

    public function testInvokeMissingRequiredOption(): void
    {
        $result = $this->invoker->invoke($this->config, ['greet']);
        $this->assertSame(1, $result->exitCode);
    }

    /** @dataProvider errorStatusProvider */
    public function testInvokeError(int $code, int $expectedExitCode): void
    {
        $errorInvoker = new CliInvoker(
            new FakeResourceFactory(),
        );
        $config = new Config(
            name: 'error',
            description: 'Error test command',
            version: '1.0.0',
            method: 'get',
            uri: 'app://self/error',
            options: [
                'code' => new CliOption(
                    name: 'code',
                    shortName: 'c',
                    description: 'Status code',
                    type: 'int',
                    isRequired: true,
                ),
            ],
            output: 'message',
        );
        $result = $errorInvoker->invoke($config, ['error', '--code=' . $code]);
        $this->assertSame($expectedExitCode, $result->exitCode);
        $this->assertNotEmpty($result->message);
    }

    public function errorStatusProvider(): array
    {
        return [
            'client error' => [400, 1],
            'not found' => [404, 1],
            'server error' => [500, 2],
        ];
    }
}
