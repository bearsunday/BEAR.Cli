<?php

declare(strict_types=1);

namespace BEAR\Cli;

use PHPUnit\Framework\TestCase;

use function array_keys;

class ArgParserTest extends TestCase
{
    private ArgParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ArgParser();
    }

    /**
     * @param array<string>              $argv
     * @param array<string, string|bool> $expected
     *
     * @dataProvider provideBasicArguments
     */
    public function testParseBasicArguments(array $argv, array $expected): void
    {
        $result = $this->parser->parseArgv($argv);
        $this->assertSame($expected, $result);
    }

    /** @return array<string, array{array<string>, array<string, string|bool>}> */
    public static function provideBasicArguments(): array
    {
        return [
            'short flag' => [
                ['script.php', '-v'],
                ['v' => true],
            ],
            'short option with value' => [
                ['script.php', '-n', 'value'],
                ['n' => 'value'],
            ],
            'long flag' => [
                ['script.php', '--verbose'],
                ['verbose' => true],
            ],
            'long option with value' => [
                ['script.php', '--name', 'value'],
                ['name' => 'value'],
            ],
            'long option with equals' => [
                ['script.php', '--name=value'],
                ['name' => 'value'],
            ],
        ];
    }

    /**
     * @param array<string>              $argv
     * @param array<string, string|bool> $expected
     *
     * @dataProvider provideMultipleArguments
     */
    public function testParseMultipleArguments(array $argv, array $expected): void
    {
        $result = $this->parser->parseArgv($argv);
        $this->assertSame($expected, $result);
    }

    /** @return array<string, array{array<string>, array<string, string|bool>}> */
    public static function provideMultipleArguments(): array
    {
        return [
            'multiple short flags' => [
                ['script.php', '-v', '-d'],
                ['v' => true, 'd' => true],
            ],
            'short and long options' => [
                ['script.php', '-n', 'value1', '--format', 'json'],
                ['n' => 'value1', 'format' => 'json'],
            ],
            'mixed flags and options' => [
                ['script.php', '-v', '--name', 'value', '--debug'],
                ['v' => true, 'name' => 'value', 'debug' => true],
            ],
            'short option and long option with equals' => [
                ['script.php', '-n', 'value1', '--format=json'],
                ['n' => 'value1', 'format' => 'json'],
            ],
        ];
    }

    /**
     * @param array<string>              $argv
     * @param array<string, string|bool> $expected
     *
     * @dataProvider provideEdgeCases
     */
    public function testHandleEdgeCases(array $argv, array $expected): void
    {
        $result = $this->parser->parseArgv($argv);
        $this->assertSame($expected, $result);
    }

    /** @return array<string, array{array<string>, array<string, string|bool>}> */
    public static function provideEdgeCases(): array
    {
        return [
            'empty argv' => [
                ['script.php'],
                [],
            ],
            'value with equals and dash' => [
                ['script.php', '--name=-value'],
                ['name' => '-value'],
            ],
            'equals with empty value' => [
                ['script.php', '--name='],
                ['name' => ''],
            ],
            'multiple equals signs' => [
                ['script.php', '--name=value=with=equals'],
                ['name' => 'value=with=equals'],
            ],
            'option at end without value' => [
                ['script.php', '--flag', '--name'],
                ['flag' => true, 'name' => true],
            ],
        ];
    }

    /**
     * @param array<string>              $argv
     * @param array<string, string|bool> $expected
     *
     * @dataProvider provideSpecialCharacters
     */
    public function testHandleSpecialCharacters(array $argv, array $expected): void
    {
        $result = $this->parser->parseArgv($argv);
        $this->assertSame($expected, $result);
    }

    /** @return array<string, array{array<string>, array<string, string|bool>}> */
    public static function provideSpecialCharacters(): array
    {
        return [
            'value with spaces' => [
                ['script.php', '--name', 'value with spaces'],
                ['name' => 'value with spaces'],
            ],
            'value with special chars' => [
                ['script.php', '--name', 'value!@#$%^&*()'],
                ['name' => 'value!@#$%^&*()'],
            ],
            'value with unicode' => [
                ['script.php', '--name', 'こんにちは'],
                ['name' => 'こんにちは'],
            ],
            'value with quotes' => [
                ['script.php', '--name', '"quoted value"'],
                ['name' => '"quoted value"'],
            ],
        ];
    }

    public function testIgnoreFlagsAfterStandaloneArguments(): void
    {
        $argv = ['script.php', 'standalone', '--flag'];
        $result = $this->parser->parseArgv($argv);
        $this->assertEmpty($result);
    }

    public function testPreserveOptionOrder(): void
    {
        $argv = [
            'script.php',
            '--first',
            'value1',
            '--second',
            'value2',
            '--third',
            'value3',
        ];

        $result = $this->parser->parseArgv($argv);

        $expected = [
            'first' => 'value1',
            'second' => 'value2',
            'third' => 'value3',
        ];

        $this->assertSame($expected, $result);
        $this->assertSame(array_keys($expected), array_keys($result));
    }

    public function testHandleOverridingOptions(): void
    {
        $argv = [
            'script.php',
            '--name',
            'first',
            '--name',
            'second',
        ];

        $result = $this->parser->parseArgv($argv);

        $this->assertSame(['name' => 'second'], $result);
    }
}
