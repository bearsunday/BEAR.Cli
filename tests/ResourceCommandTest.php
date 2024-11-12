<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Fake\FakeResourceFactory;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;

use function json_decode;

class ResourceCommandTest extends TestCase
{
    private ResourceInterface $resource;
    private Config $config;
    private ResourceCommand $command;

    protected function setUp(): void
    {
        $this->resource = new FakeResourceFactory();
        $this->config = new Config(
            name: 'greeting',
            description: 'Say hello in multiple languages',
            version: '0.1.0',
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
                    description: 'Language (en, ja, fr)',
                    type: 'string',
                    isRequired: false,
                    defaultValue: 'en',
                ),
            ],
            output: 'greeting',
        );
        $this->command = new ResourceCommand($this->config, $this->resource);
    }

    public function testExecuteHelp(): void
    {
        $result = ($this->command)(['greeting', '--help']);
        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('Say hello in multiple languages', $result->message);
        $this->assertStringContainsString('--name, -n', $result->message);
        $this->assertStringContainsString('--lang, -l', $result->message);
    }

    public function testExecuteVersion(): void
    {
        $result = ($this->command)(['greeting', '--version']);

        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('greeting version 0.1.0', $result->message);
    }

    public function testExecuteWithRequiredOption(): void
    {
        $result = ($this->command)(['greeting', '--name', 'BEAR']);

        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('Hello, BEAR', $result->message);
    }

    public function testExecuteWithOptionalOption(): void
    {
        $result = ($this->command)(['greeting', '--name', 'BEAR', '--lang', 'ja']);

        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('こんにちは, BEAR', $result->message);
    }

    public function testExecuteWithJsonFormat(): void
    {
        $result = ($this->command)(['greeting', '--name', 'BEAR', '--format', 'json']);

        $this->assertSame(0, $result->exitCode);
        $json = json_decode($result->message, true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('greeting', $json);
        $this->assertArrayHasKey('lang', $json);
    }

    public function testExecuteMissingRequiredOption(): void
    {
        $result = ($this->command)(['greeting']);

        $this->assertSame(1, $result->exitCode);
        $this->assertStringContainsString('Option --name is required', $result->message);
    }

    public function testExecuteWithInvalidOption(): void
    {
        $result = ($this->command)(['greeting', '--invalid-option']);

        $this->assertSame(1, $result->exitCode);
        $this->assertStringContainsString('Error: Option --name is required', $result->message);
    }

    /** @dataProvider statusCodeProvider */
    public function testExecuteWithStatusCode(int $statusCode, int $expectedExitCode): void
    {
        $config = new Config(
            name: 'error',
            description: 'Error test',
            version: '0.1.0',
            method: 'get',
            uri: 'app://self/error',
            options: [
                'code' => new CliOption(
                    name: 'code',
                    shortName: 'c',
                    description: 'Status code',
                    type: 'string',
                    isRequired: true,
                ),
            ],
            output: 'message',
        );
        $command = new ResourceCommand($config, new FakeResourceFactory());
        $result = $command(['error', '--code', (string) $statusCode]);

        $this->assertSame($expectedExitCode, $result->exitCode);
    }

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
