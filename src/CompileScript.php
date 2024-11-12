<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\AppMeta\Meta;
use BEAR\Cli\Attribute\Cli;
use ReflectionClass;
use ReflectionMethod;

use function chmod;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function sprintf;

final class CompileScript
{
    private const CLI_DIR = 'bin/cli';

    public function __construct(
        private readonly GenScript $genScript,
    ) {
    }

    /** @return array<CommandSource> */
    public function compile(Meta $meta): array
    {
        $sources = [];
        $generator = $meta->getGenerator();
        foreach ($generator as $resource) {
            $class = new ReflectionClass($resource->class);
            $methods = $class->getMethods();
            foreach ($methods as $method) {
                $cliAttr = $this->getCliAttribute($method);
                if (! $cliAttr) {
                    continue;
                }

                $sources[] = ($this->genScript)(
                    uri: $resource->uriPath,
                    appDir: $meta->appDir,
                    method: $method,
                );
            }
        }

        $this->dumpSources($sources, $meta->appDir . '/' . self::CLI_DIR);

        return $sources;
    }

    /** @param array<CommandSource> $sources */
    private function dumpSources(array $sources, string $cliDir): void
    {
        if (! is_dir($cliDir)) {
            mkdir($cliDir, 0755, true);
        }

        foreach ($sources as $source) {
            $file = sprintf('%s/%s', $cliDir, $source->name);
            file_put_contents($file, $source->code);
            chmod($file, 0755);
        }
    }

    private function getCliAttribute(ReflectionMethod $method): Cli|null
    {
        $attrs = $method->getAttributes(Cli::class);
        if (! $attrs) {
            return null;
        }

        return $attrs[0]->newInstance();
    }
}
