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
    public function __construct(
        private readonly GenScript $genScript,
        private readonly GenFormula $genFormula,
    ) {
    }

    /**
     * Compile CLI commands and generate formula
     *
     * @return array{
     *   sources: array<CommandSource>,
     *   formula: array{path: string, content: string}|null
     * }
     */
    public function compile(Meta $meta): array
    {
        $sources = [];
        $cliDesc = '';
        $generator = $meta->getGenerator();

        foreach ($generator as $resource) {
            $class = new ReflectionClass($resource->class);
            $methods = $class->getMethods();
            foreach ($methods as $method) {
                $cliAttr = $this->getCliAttribute($method);
                if (! $cliAttr) {
                    continue;
                }

                // Get CLI description for formula
                $cliDesc = $cliAttr->description;

                $sources[] = ($this->genScript)(
                    uri: $resource->uriPath,
                    appDir: $meta->appDir,
                    method: $method,
                );
            }
        }

        // Generate formula if it's a git repository
        $formula = null;
        if (is_dir($meta->appDir . '/.git')) {
            $formula = ($this->genFormula)($meta, $cliDesc);
        }

        $this->dumpSources($sources, $meta->appDir . '/bin');

        return [
            'sources' => $sources,
            'formula' => $formula,
        ];
    }

    /** @param array<CommandSource> $sources */
    private function dumpSources(array $sources, string $binDir): void
    {
        if (! is_dir($binDir)) {
            mkdir($binDir);
        }

        foreach ($sources as $source) {
            $file = sprintf('%s/%s', $binDir, $source->name);
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
