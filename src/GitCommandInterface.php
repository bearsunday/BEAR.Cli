<?php

declare(strict_types=1);

namespace BEAR\Cli;

interface GitCommandInterface
{
    public function getRemoteUrl(): string;

    public function detectMainBranch(string $repoUrl): string;
}
