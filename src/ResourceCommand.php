<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Exception\RuntimeException;
use BEAR\Resource\JsonRenderer;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use Throwable;

use function array_keys;
use function assert;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;

final class ResourceCommand
{
    private ArgParser $argParser;

    public function __construct(
        private readonly Config $config,
        private readonly ResourceInterface $resource,
    ) {
        $this->argParser = new ArgParser();
    }

    /** @param array<string> $argv */
    public function __invoke(array $argv): CommandResult
    {
        try {
            if ($this->shouldShowHelp($argv)) {
                return new CommandResult(
                    $this->buildHelpMessage($this->config),
                    0,
                );
            }

            if ($this->shouldShowVersion($argv)) {
                return new CommandResult(
                    sprintf('%s version %s', $this->config->name, $this->config->version),
                    0,
                );
            }

            $options = $this->argParser->parseArgv($argv);
            $params = $this->buildParams($this->config, $options);

            // invoke resource request
            $result = $this->resource->{$this->config->method}($this->config->uri, $params);
            assert($result instanceof ResourceObject);

            // set exit code based on response status code
            if ($result->code >= 400) {
                return new CommandResult(
                    (string) $result,
                    $result->code >= 500 ? 2 : 1,
                );
            }

            if (isset($options['format']) && $options['format'] === 'json') {
                $result->setRenderer(new JsonRenderer());

                return new CommandResult((string) $result);
            }

            if (is_array($result->body) && in_array($this->config->output, array_keys($result->body))) {
                $outputMessage = $result->body[$this->config->output];
                assert(is_string($outputMessage));

                return new CommandResult(
                    $outputMessage,
                    0,
                );
            }

            // json format by default
            $result->setRenderer(new JsonRenderer());

            return new CommandResult((string) $result);
        } catch (RuntimeException $e) {
            return new CommandResult(
                sprintf('Error: %s', $e->getMessage()),
                1,
            );
        } catch (Throwable $e) {
            return new CommandResult(
                sprintf('Error: %s(%s)', $e::class, $e->getMessage()),
                2,
            );
        }
    }

    /**
     * Build resource request parameters from command line options
     *
     * @param array<string, bool|string> $options getoptの結果
     *
     * @return array<string, mixed>
     *
     * @psalm-external-mutation-free
     */
    private function buildParams(Config $config, array $options): array
    {
        $params = [];
        foreach ($config->options as $name => $option) {
            $value = null;

            // short option confirmation
            if ($option->shortName && isset($options[$option->shortName])) {
                $value = $options[$option->shortName];
            }

            // Check long-form option values (precedence over short-form)
            if (isset($options[$name])) {
                $value = $options[$name];
            }

            // Check the required options
            if ($option->isRequired && $value === null) {
                throw new RuntimeException("Option --{$name} is required");
            }

            // If no value is specified, the default value is used
            /** @psalm-suppress  MixedAssignment */
            $params[$name] = $value ?? $option->defaultValue;
        }

        return $params;
    }

    private function buildHelpMessage(Config $config): string
    {
        $help = "{$config->description}\n\n";
        $help .= "Usage: {$config->name} [options]\n\n";
        $help .= "Options:\n";

        foreach ($config->options as $option) {
            $shortOpt = $option->shortName ? ", -{$option->shortName}" : '';
            $required = $option->isRequired ? ' (required)' : '';
            $default = $option->defaultValue !== null && is_string($option->defaultValue) ? " (default: {$option->defaultValue})" : '';
            $help .= sprintf(
                "  --%s%s\t%s%s%s\n",
                $option->name,
                $shortOpt,
                $option->description,
                $required,
                $default,
            );
        }

        $help .= "  --help, -h\t\tShow this help message\n";
        $help .= "  --version, -v\t\tShow version information\n";
        $help .= "  --format\t\tOutput format (text|json) (default: text)\n";

        return $help;
    }

    /**
     * @param array<string> $argv
     *
     * @psalm-pure
     */
    private function shouldShowHelp(array $argv): bool
    {
        return in_array('--help', $argv) || in_array('-h', $argv);
    }

    /**
     * @param array<string> $argv
     *
     * @psalm-pure
     */
    private function shouldShowVersion(array $argv): bool
    {
        return in_array('--version', $argv) || in_array('-v', $argv);
    }
}
