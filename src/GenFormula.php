<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\AppMeta\Meta;
use BEAR\Cli\Exception\FormulaException;
use Throwable;

use function is_dir;
use function preg_match;
use function sprintf;
use function str_replace;
use function strtolower;
use function ucfirst;

use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;

/**
 * Generate Homebrew formula for the application
 *
 * @psalm-type Formula=array{path: string, content: string}
 * @psalm-type RepoInfo=array{org: string, repo: string}
 */
final class GenFormula
{
    private const GITHUB_REPOSITORY_PATTERN = '#github\.com[:/]([^/]+)/([^/]+?)(?:\.git)?$#';
    private const TEMPLATE = <<<'EOT'
# typed: false
# frozen_string_literal: true

class %s < Formula
  desc "Command line interface for %s application"
  homepage "%s"
  head "%s", branch: "%s"
  license "MIT"

  depends_on "php@%s"
  depends_on "composer"

  def install
    libexec.install Dir["*"]

    cd libexec do
      system "composer", "install", "--prefer-dist", "--no-dev", "--no-interaction" or raise "Composer install failed"
      system "mkdir", "-p", "bin" unless File.directory?("bin")

      # Generate CLI commands and get the generated command name
      output = Utils.safe_popen_read("#{libexec}/vendor/bear/cli/bin/bear-cli-gen", "%s")
      # Extract multiple commands from the output
      generated_commands = output.scan(/CLI commands have been generated.*?:
\s+(.+)$/m)[0][0].split(/\s+/)
      ohai "Generated commands:", generated_commands.join(", ")

      generated_commands.each do |command|
        if File.exist?("bin/cli/#{command}")
          bin.mkpath
          mv "bin/cli/#{command}", bin/command
          chmod 0755, bin/command
        end
      end
    end
  end

  test do
    bin_files = Dir["#{bin}/*"]
    if bin_files.empty?
      raise "No files found in #{bin}. Installation may have failed."
    end

    bin_files.each do |cmd|
      assert system("test", "-x", cmd), "#{cmd} is not executable"
      assert_match "Usage:", shell_output("#{cmd} --help"), "Help command failed for #{cmd}"
    end
  end
end
EOT;
    public const HOMEBREW_FORMULA_PATH = '%s/var/homebrew/%s.rb';

    public function __construct(
        private readonly GitCommandInterface $gitCommand,
    ) {
    }

    /** @return RepoInfo */
    private function extractRepoInfo(string $url): array
    {
        if (! preg_match(self::GITHUB_REPOSITORY_PATTERN, $url, $matches)) {
            throw new FormulaException(
                'Invalid GitHub URL format. URL must be in format: https://github.com/owner/repo or git@github.com:owner/repo',
            );
        }

        return [
            'org' => $matches[1],
            'repo' => $matches[2],
        ];
    }

    private function generateFormulaName(string $repoName): string
    {
        $name = str_replace(['.', '-'], '', $repoName);

        return strtolower($name);
    }

    /** @return Formula */
    public function __invoke(Meta $meta): array
    {
        if (! is_dir($meta->appDir . '/.git')) {
            throw new FormulaException('Not a git repository. Initialize a git repository first with: git init'); // @codeCoverageIgnore
        }

        try {
            $remoteUrl = $this->gitCommand->getRemoteUrl();
            if (empty($remoteUrl)) {
                throw new FormulaException('Git remote URL is not configured. Set a remote with: git remote add origin <url>');
            }
        } catch (Exception $e) {
            throw new FormulaException('Failed to get Git remote URL: ' . $e->getMessage());
        }

        $repoInfo = $this->extractRepoInfo($remoteUrl);

        try {
            $branch = $this->gitCommand->detectMainBranch($remoteUrl);
        } catch (Throwable $e) {
            throw new FormulaException('Failed to detect main branch: ' . $e->getMessage());
        }

        $formulaName = $this->generateFormulaName($repoInfo['repo']);

        $content = sprintf(
            self::TEMPLATE,
            ucfirst($formulaName),
            $meta->name,
            "https://github.com/{$repoInfo['org']}/{$repoInfo['repo']}",
            $remoteUrl,
            $branch,
            PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            str_replace('\\', '\\\\', $meta->name),
        );

        $path = sprintf(
            self::HOMEBREW_FORMULA_PATH,
            $meta->appDir,
            $formulaName,
        );

        return [
            'path' => $path,
            'content' => $content,
        ];
    }
}
