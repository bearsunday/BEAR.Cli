<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\AppMeta\Meta;
use BEAR\Cli\Attribute\Cli;
use Ray\Aop\ReflectionClass;
use ReflectionMethod;

use function chmod;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function sprintf;

final class CompileScript
{
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

        return $this->dumpSources($sources, $meta->appDir . '/bin');
    }

    /**
     * @param array<CommandSource> $sources
     *
     * @return array<CommandSource>
     */
    private function dumpSources(array $sources, string $binDir): array
    {
        if (! is_dir($binDir)) {
            mkdir($binDir);
        }

        foreach ($sources as $source) {
            $file = sprintf('%s/%s', $binDir, $source->name);
            file_put_contents($file, $source->code);
            chmod($file, 0755);
        }

        return $sources;
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
