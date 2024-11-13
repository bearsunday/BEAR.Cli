<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\AppMeta\Meta;
use BEAR\Cli\Exception\RuntimeException;

use function exec;
use function preg_match;
use function rtrim;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function ucfirst;

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
      system "composer", "install", "--prefer-dist"
      
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

    private function getRepositoryUrl(): string
    {
        exec('git config --get remote.origin.url', $output, $resultCode);

        if ($resultCode !== 0 || empty($output[0])) {
            throw new RuntimeException('Failed to get repository URL');
        }

        $url = $output[0];
        if (str_starts_with($url, 'git@github.com:')) {
            $url = str_replace('git@github.com:', 'https://github.com/', $url);
        }

        return rtrim($url, '/');
    }

    private function detectMainBranch(string $repoUrl): string
    {
        exec("git ls-remote --heads {$repoUrl} | sort | tail -n1 | awk '{print \$2}' | cut -d '/' -f 3", $output, $resultCode);

        if ($resultCode !== 0 || empty($output[0])) {
            throw new RuntimeException('Failed to detect main branch');
        }

        return $output[0];
    }

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

    /** @return array{path: string, content: string} */
    public function __invoke(Meta $meta, string $description): array
    {
        $repoUrl = $this->getRepositoryUrl();
        $repoInfo = $this->extractRepoInfo($repoUrl);
        $branch = $this->detectMainBranch($repoUrl);

        // Generate formula/tap name
        $formulaName = $this->generateFormulaName($repoInfo['repo']);

        // Generate formula content
        $content = sprintf(
            self::TEMPLATE,
            ucfirst($formulaName), // 先頭文字を大文字にしたクラス名
            $description,
            "https://github.com/{$repoInfo['org']}/{$repoInfo['repo']}",
            $repoUrl,
            $branch,
            $meta->name,
        );

        // Define formula path
        $path = sprintf(
            '%s/var/homebrew/homebrew-%s/Formula/%s.rb',
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
