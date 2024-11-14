<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\AppMeta\Meta;
use BEAR\Cli\Exception\RuntimeException;

use function preg_match;
use function sprintf;
use function str_replace;
use function strtolower;
use function ucfirst;

use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;

/**
 * @psalm-type RepoInfo = array{org: string, repo: string}
 * @psalm-type Formula = array{path: string, content: string}
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
      generated_commands = output.scan(/CLI commands have been generated.*?:\n\s+(.+)$/m)[0][0].split(/\s+/)
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
            throw new RuntimeException('Invalid GitHub URL format');
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
        $repoUrl = $this->gitCommand->getRemoteUrl();

        $repoInfo = $this->extractRepoInfo($repoUrl);
        $branch = $this->gitCommand->detectMainBranch($repoUrl);
        $repoUrl = $this->gitCommand->getRemoteUrl();

        // Generate formula/tap name
        $formulaName = $this->generateFormulaName($repoInfo['repo']);

        // Generate formula content
        $content = sprintf(
            self::TEMPLATE,
            ucfirst($formulaName),
            $meta->name,
            "https://github.com/{$repoInfo['org']}/{$repoInfo['repo']}",
            $repoUrl,
            $branch,
            PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            str_replace('\\', '\\\\', $meta->name),
        );

        // Define formula path
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
