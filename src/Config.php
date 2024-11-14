<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;
use ReflectionMethod;

use function strtolower;
use function substr;

/**
 * Command configuration value object
 */
final readonly class Config
{
    private const REQUIRED = ':';
    private const OPTIONAL = '::';

    public readonly string $name;
    public readonly string $description;
    public readonly string $version;
    public readonly string $method;

    /** @var array<string, CliOption> */
    public readonly array $options;
    public readonly string $output;
    public readonly string $shortOptions;

    /** @var array<string> */
    public readonly array $longOptions;

    /** @throws Exception\LogicException */
    public function __construct(
        public readonly string $uri,
        ReflectionMethod $method,
    ) {
        $cliAttr = $this->getCliAttribute($method);
        if (! $cliAttr) {
            throw new Exception\LogicException('No CLI attribute found');
        }

        $this->name = $cliAttr->name;
        $this->description = $cliAttr->description;
        $this->version = $cliAttr->version;
        $this->method = strtolower(substr($method->getName(), 2));
        $this->options = $this->getOptions($method);
        $this->output = $cliAttr->output;
        $this->shortOptions = $this->buildShortOptions();
        $this->longOptions = $this->buildLongOptions();
    }

    private function getCliAttribute(ReflectionMethod $method): Cli|null
    {
        $attrs = $method->getAttributes(Cli::class);
        if (! $attrs) {
            return null;
        }

        return $attrs[0]->newInstance();
    }

    /** @return array<string, CliOption> */
    private function getOptions(ReflectionMethod $method): array
    {
        $options = [];
        foreach ($method->getParameters() as $param) {
            $attrs = $param->getAttributes(Option::class);
            if (! $attrs) {
                continue; // @codeCoverageIgnore
            }

            $attr = $attrs[0]->newInstance();
            $options[$param->getName()] = new CliOption(
                name: $param->getName(),
                shortName: $attr->shortName,
                description: $attr->description,
                isRequired: ! $param->isOptional(),
                defaultValue: $param->isOptional() ? $param->getDefaultValue() : null,
            );
        }

        return $options;
    }

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
