<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

use function chdir;
use function file_exists;
use function getcwd;

final class GitCommandTest extends TestCase
{
    private GitCommand $gitCommand;
    private string|false $originalCwd;

    protected function setUp(): void
    {
        $this->gitCommand = new GitCommand();
        $this->originalCwd = getcwd();
        // Change to the project root which has a .git directory
        chdir(__DIR__ . '/..');
    }

    protected function tearDown(): void
    {
        if ($this->originalCwd !== false) {
            chdir($this->originalCwd);
        }
    }

    public function testGetRemoteUrl(): void
    {
        // Skip if not in a git repository with remote configured
        if (! file_exists('.git/config')) {
            $this->markTestSkipped('Not in a git repository');
        }

        try {
            $url = $this->gitCommand->getRemoteUrl();

            $this->assertIsString($url);
            $this->assertNotEmpty($url);
            // Should contain github.com since this is a GitHub repository
            $this->assertStringContainsString('github.com', $url);
        } catch (RuntimeException $e) {
            // If remote is not configured, skip the test
            $this->markTestSkipped('Git remote is not configured: ' . $e->getMessage());
        }
    }

    public function testDetectMainBranch(): void
    {
        $repoUrl = 'https://github.com/bearsunday/BEAR.Cli.git';
        $branch = $this->gitCommand->detectMainBranch($repoUrl);

        $this->assertIsString($branch);
        $this->assertNotEmpty($branch);
        // The branch name should not be empty
        $this->assertGreaterThan(0, strlen($branch));
    }

    public function testGetRemoteUrlThrowsExceptionWhenNotInGitRepository(): void
    {
        // Change to a directory without .git
        chdir('/tmp');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to execute command');

        $this->gitCommand->getRemoteUrl();
    }
}
