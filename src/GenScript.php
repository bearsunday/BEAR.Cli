<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Attribute\Cli;
use ReflectionMethod;

use function explode;
use function implode;
use function sprintf;

final class GenScript
{
    private const TEMPLATE = <<<'EOT'
#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace %s;

require '%s/vendor/autoload.php';

use BEAR\Package\Injector;
use BEAR\Resource\ResourceInterface;
use %s;
use BEAR\Cli\Config;
use BEAR\Cli\ResourceCommand;

$resource = Injector::getInstance('%s', 'prod-app', '%s')->getInstance(ResourceInterface::class);
$config = new Config('%s', new \ReflectionMethod(\%s::class, '%s'));
$command = new ResourceCommand($config, $resource);
$result = $command($argv);

echo $result->message . PHP_EOL;
exit($result->exitCode);

EOT;

    /**
     * Generate script code
     */
    public function __invoke(string $uri, string $appDir, ReflectionMethod $method): CommandSource
    {
        $cliAttr = $this->getCliAttribute($method);
        if (! $cliAttr) {
            throw new Exception\LogicException('No CLI attribute found');
        }

        $declaringClass = $method->getDeclaringClass();
        $namespaceParts = explode('\\', $declaringClass->getNamespaceName());
        $appName = implode('\\', [$namespaceParts[0], $namespaceParts[1]]);

        return new CommandSource(
            name: $cliAttr->name,
            code: sprintf(
                self::TEMPLATE,
                $declaringClass->getNamespaceName(),
                $appDir,  // for autoload.php
                $declaringClass->getName(),
                $appName,
                $appDir,  // for Injector
                $uri,
                $declaringClass->getName(),
                $method->getName(),
            ),
        );
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
