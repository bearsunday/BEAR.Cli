<?php

declare(strict_types=1);

namespace BEAR\Cli;

use function array_shift;
use function count;
use function explode;
use function str_starts_with;
use function substr;

/**
 * Command line argument parser
 *
 * @psalm-immutable
 */
final class ArgParser
{
    /**
     * Parse command line arguments manually
     *
     * @param array<string> $argv Command line arguments
     *
     * @return array<string, string|bool> Parsed options
     */
    public function parseArgv(array $argv): array
    {
        $options = [];
        array_shift($argv);

        for ($i = 0; $i < count($argv); $i++) {
            $arg = $argv[$i];
            $nextArg = $argv[$i + 1] ?? null;
            $shortFlag = str_starts_with($arg, '-');
            $longFlag = str_starts_with($arg, '--');

            if ($longFlag) {
                $result = $this->parseLongFormat($arg, $nextArg);
                $options[$result->name] = $result->value;
                $i += $result->increment;
                continue;
            }

            if ($shortFlag) {
                $result = $this->parseShortFormat($arg, $nextArg);
                $options[$result->name] = $result->value;
                $i += $result->increment;
                continue;
            }
        }

        return $options;
    }

    /**
     * Parse long format options (e.g. --name=value or --name value)
     */
    private function parseLongFormat(string $arg, string|null $nextArg): ParseResult
    {
        $parts = explode('=', substr($arg, 2), 2);
        if (isset($parts[1])) {
            return new ParseResult($parts[0], $parts[1], 0);
        }

        if ($nextArg && ! str_starts_with($nextArg, '-')) {
            return new ParseResult($parts[0], $nextArg, 1);
        }

        return new ParseResult($parts[0], true, 0);
    }

    /**
     * Parse short format options (e.g. -n value)
     */
    private function parseShortFormat(string $arg, string|null $nextArg): ParseResult
    {
        $name = substr($arg, 1);
        if ($nextArg && ! str_starts_with($nextArg, '-')) {
            return new ParseResult($name, $nextArg, 1);
        }

        return new ParseResult($name, true, 0);
    }
}
