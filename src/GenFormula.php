<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\AppMeta\Meta;
use BEAR\Cli\Exception\RuntimeException;

final class GenFormula
{
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
        if (! preg_match('#github\.com[:/]([^/]+)/([^/\.]+)#', $url, $matches)) {
            throw new RuntimeException('Invalid GitHub URL format');
        }

        return [
            'org' => $matches[1],
            'repo' => $matches[2]
        ];
    }

    /**
     * @return array{path: string, content: string}
     */
    public function __invoke(Meta $meta): array
    {
        $repoUrl = $this->getRepositoryUrl();
        $repoInfo = $this->extractRepoInfo($repoUrl);
        $branch = $this->detectMainBranch($repoUrl);

        // Get description from composer.json
        $composerJson = json_decode(file_get_contents($meta->appDir . '/composer.json'), true);
        $description = $composerJson['description'] ?? "CLI commands for {$repoInfo['repo']}";

        // Generate formula name
        $formulaName = str_replace(['.', '-'], '', strtolower($repoInfo['repo']));

        // Generate formula content
        $content = sprintf(
            self::TEMPLATE,
            ucfirst($formulaName),
            $description,
            "https://github.com/{$repoInfo['org']}/{$repoInfo['repo']}",
            $repoUrl,
            $branch,
            $meta->name
        );

        // Define formula path
        $path = sprintf(
            '%s/homebrew-%s/Formula/%s.rb',
            dirname($meta->appDir),
            $formulaName,
            $formulaName
        );

        return [
            'path' => $path,
            'content' => $content
        ];
    }
}
