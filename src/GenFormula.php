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
  desc "%s"
  homepage "%s"
  head "%s", branch: "%s"
  license "MIT"

  depends_on "php"
  depends_on "composer"

  def install
    libexec.install Dir["*"]

    cd libexec do
       system "composer", "install", "--prefer-dist", "--no-dev", "--no-interaction" or raise "Composer install failed"
      
      # Generate CLI commands and get the generated command name
      output = Utils.safe_popen_read("#{libexec}/vendor/bear/cli/bin/bear-cli-gen", "%s")
      generated_command = output.match(/CLI commands have been generated.*:\n\s+(\w+)/)[1]
      
      if File.exist?("bin/#{generated_command}")
        bin.mkpath
        mv "bin/#{generated_command}", bin/generated_command
        chmod 0755, bin/generated_command
      end
    end
  end

  test do
    Dir["#{bin}/*"].each do |cmd|
      assert_match "Usage:", shell_output("#{cmd} --help")
    end
  end
end
EOT;
    public const HOMEBREW_FORMULA_PATH = '%s/var/homebrew/homebrew-%s/Formula/%s.rb';

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
    public function __invoke(Meta $meta, string $description): array
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
            $description,
            "https://github.com/{$repoInfo['org']}/{$repoInfo['repo']}",
            $repoUrl,
            $branch,
            $meta->name,
        );

        // Define formula path
        $path = sprintf(
            self::HOMEBREW_FORMULA_PATH,
            $meta->appDir,
            $formulaName,
            $formulaName,
        );

        return [
            'path' => $path,
            'content' => $content,
        ];
    }
}
