<?php

declare(strict_types=1);

namespace BEAR\Cli;

use function array_shift;
use function count;
use function explode;
use function str_starts_with;
use function substr;

/**
 * Command line argument parser following POSIX/GNU style conventions:
 * - Short options start with a single dash (-v, -a, -b)
 * - Long options start with double dashes (--verbose, --all)
 * - Multiple short options can be combined (-vab is equivalent to -v -a -b)
 * - Options may have values:
 *   - Short: -n value or -n=value
 *   - Long: --name value or --name=value
 *
 * @see https://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html
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
        array_shift($argv); // Remove script name

        $foundStandalone = false;
        for ($i = 0; $i < count($argv); $i++) {
            $arg = $argv[$i];

            // Check for standalone argument
            if (! str_starts_with($arg, '-')) {
                $foundStandalone = true;
                continue;
            }

            // If we've found a standalone argument, ignore subsequent flags
            if ($foundStandalone) {
                continue;
            }

            $nextArg = $argv[$i + 1] ?? null;
            $isLongOption = str_starts_with($arg, '--');

            if ($isLongOption) {
                $result = $this->parseLongFormat($arg, $nextArg);
                $options[$result->name] = $result->value;
                $i += $result->increment;
                continue;
            }

            // Handle short format
            $result = $this->parseShortFormat($arg, $nextArg);
            $options[$result->name] = $result->value;
            $i += $result->increment;
        }

        return $options;
    }

    /**
     * Parse long format options (e.g. --name=value or --name value)
     */
    private function parseLongFormat(string $arg, string|null $nextArg): ParseResult
    {
        $parts = explode('=', substr($arg, 2), 2);

        // Handle --option=value format
        if (isset($parts[1])) {
            return new ParseResult($parts[0], $parts[1], 0);
        }

        // Handle --option value format
        if ($nextArg !== null && ! str_starts_with($nextArg, '-')) {
            return new ParseResult($parts[0], $nextArg, 1);
        }

        // Handle flag-only options
        return new ParseResult($parts[0], true, 0);
    }

    /**
     * Parse short format options (e.g. -n value)
     */
    private function parseShortFormat(string $arg, string|null $nextArg): ParseResult
    {
        $name = substr($arg, 1);

        // Handle -n value format
        if ($nextArg !== null && ! str_starts_with($nextArg, '-')) {
            return new ParseResult($name, $nextArg, 1);
        }

        // Handle flag-only options (-v, -d, etc.)
        return new ParseResult($name, true, 0);
    }
}
