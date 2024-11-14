<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\AppMeta\Meta;
use BEAR\Cli\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function mkdir;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function ucfirst;
use function uniqid;

final class GenFormulaTest extends TestCase
{
    private string $tmpDir;
    private Meta $meta;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/bear-cli-test-' . uniqid();
        mkdir($this->tmpDir);
        mkdir($this->tmpDir . '/.git');

        // Setup Meta
        $this->meta = new Meta('FakeVendor\FakeProject');
        $ref = new ReflectionClass($this->meta);
        $prop = $ref->getProperty('appDir');
        $prop->setValue($this->meta, $this->tmpDir);
    }

    protected function tearDown(): void
    {
        rmdir($this->tmpDir . '/.git');
        rmdir($this->tmpDir);
    }

    /** @test */
    public function generatesFormulaSuccessfully(): void
    {
        $gitCommand = new class implements GitCommandInterface {
            public function getRemoteUrl(): string
            {
                return 'https://github.com/fakevendor/fake-project.git';
            }

            public function detectMainBranch(string $repoUrl): string
            {
                return 'main';
            }
        };
        $genFormula = new GenFormula($gitCommand);
        $result = $genFormula($this->meta);

        // Check formula path
        $expectedPath = sprintf(
            GenFormula::HOMEBREW_FORMULA_PATH,
            $this->tmpDir,
            'fakeproject',
        );
        $this->assertSame($expectedPath, $result['path']);

        // Check formula content
        $content = $result['content'];
        $this->assertStringContainsString('class Fakeproject < Formula', $content);
        $this->assertStringContainsString('desc "Command line interface for FakeVendor\FakeProject application"', $content);
        $this->assertStringContainsString('homepage "https://github.com/fakevendor/fake-project"', $content);
        $this->assertStringContainsString('head "https://github.com/fakevendor/fake-project.git"', $content);
        $this->assertStringContainsString('depends_on "php@', $content);
        $this->assertStringContainsString('depends_on "composer"', $content);
        $this->assertStringContainsString('bear-cli-gen", "FakeVendor\\\\FakeProject', $content);
    }

    /** @test */
    public function throwsExceptionForInvalidGithubUrl(): void
    {
        $gitCommand = new class implements GitCommandInterface {
            public function getRemoteUrl(): string
            {
                return 'https://invalid-url';
            }

            public function detectMainBranch(string $repoUrl): string
            {
                return 'main';
            }
        };
        $genFormula = new GenFormula($gitCommand);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid GitHub URL format');

        $genFormula($this->meta);
    }

    /**
     * @test
     * @dataProvider provideRepositoryNames
     */
    public function generatesCorrectFormulaNameForVariousRepositories(
        string $repoName,
        string $expectedFormulaName,
    ): void {
        $gitCommand = new class ($repoName) implements GitCommandInterface {
            public function __construct(
                private readonly string $repoName,
            ) {
            }

            public function getRemoteUrl(): string
            {
                return "https://github.com/fakevendor/{$this->repoName}";
            }

            public function detectMainBranch(string $repoUrl): string
            {
                return 'main';
            }
        };
        $genFormula = new GenFormula($gitCommand);

        $result = $genFormula($this->meta);

        $this->assertStringContainsString(
            sprintf('class %s < Formula', ucfirst($expectedFormulaName)),
            $result['content'],
            "Failed for repository name: {$repoName}",
        );
    }

    /** @return array<string, array{string, string}> */
    public static function provideRepositoryNames(): array
    {
        return [
            'simple project name' => ['simple-project', 'simpleproject'],
            'project with dots' => ['my.awesome.project', 'myawesomeproject'],
            'uppercase project' => ['UPPER-case-PROJECT', 'uppercaseproject'],
            'mixed case project' => ['mixed.Case-Project', 'mixedcaseproject'],
        ];
    }

    /** @test */
    public function handlesSSHUrlConversion(): void
    {
        $gitCommand = new class implements GitCommandInterface {
            public function getRemoteUrl(): string
            {
                return 'git@github.com:fakevendor/fake-project.git';
            }

            public function detectMainBranch(string $repoUrl): string
            {
                return 'main';
            }
        };
        $genFormula = new GenFormula($gitCommand);

        $result = $genFormula($this->meta);

        $this->assertStringContainsString(
            'homepage "https://github.com/fakevendor/fake-project"',
            $result['content'],
        );
    }
}
