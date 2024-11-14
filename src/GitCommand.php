<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Exception\RuntimeException;

use function exec;

final class GitCommand implements GitCommandInterface
{
    private function exec(string $command): string
    {
        exec($command, $output, $resultCode);

        if ($resultCode !== 0 || empty($output[0])) {
            throw new RuntimeException('Failed to execute command: ' . $command); // @codeCoverageIgnore
        }

        return (string) $output[0];
    }

    public function getRemoteUrl(): string
    {
        if (! $this->exec('command -v git')) {
            throw new RuntimeException('Git is not installed or not in PATH'); // @codeCoverageIgnore
        }

        return $this->exec('git config --get remote.origin.url');
    }

    public function detectMainBranch(string $repoUrl): string
    {
        return $this->exec(
            "git ls-remote --heads {$repoUrl} | sort | tail -n1 | awk '{print \$2}' | cut -d '/' -f 3",
        );
    }
}
