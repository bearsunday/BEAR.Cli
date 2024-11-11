<?php

declare(strict_types=1);

namespace BEAR\Cli;

/**
 * Command configuration value object
 *
 * @psalm-immutable
 */
final class Config
{
    private const string REQUIRED = ':';
    private const string OPTIONAL = '::';
    public readonly string $shortOptions;
    public readonly array $longOptions;

    /** @param array<string, CliOption> $options */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $version,
        public readonly string $method,
        public readonly string $uri,
        public readonly array $options,
        public readonly string $output,
    ) {
        $this->shortOptions = $this->buildShortOptions();
        $this->longOptions = $this->buildLongOptions();
    }

    /** @return array<string> */
    private function buildShortOptions(): string
    {
        $shortOpts = 'hv'; // help & version
        foreach ($this->options as $option) {
            if ($option->shortName) {
                $suffix = $option->isRequired ? self::REQUIRED : self::OPTIONAL;
                $shortOpts .= "{$option->shortName}{$suffix}";
            }
        }

        return $shortOpts;
    }

    /** @return array<string> */
    private function buildLongOptions(): array
    {
        $longOpts = [
            'help',
            'version',
            'format::',
        ];
        foreach ($this->options as $option) {
            $suffix = $option->isRequired ? self::REQUIRED : self::OPTIONAL;
            $longOpts[] = "{$option->name}{$suffix}";
        }

        return $longOpts;
    }
}
