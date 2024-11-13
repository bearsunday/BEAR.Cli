# BEAR.Cli

[![Continuous Integration](https://github.com/bearsunday/BEAR.Cli/workflows/Continuous%20Integration/badge.svg)](https://github.com/bearsunday/BEAR.Cli/actions)

Transform BEAR.Sunday resources into native CLI commands.

## Overview

BEAR.Cli converts your BEAR.Sunday API endpoints into full-featured CLI commands with Homebrew integration. While BEAR.Sunday already allows command-line access to resources:

```bash
$ php page.php '/greeting?name=World&lang=ja'
{
    "greeting": "Hello, World",
    "timestamp": 1699686400,
    "lang": "ja"
}
```

BEAR.Cli transforms these into native CLI commands with complete Homebrew integration:

```bash
# Install via Homebrew
$ brew tap your-vendor/my-project
$ brew install my-project

# Use as a native command with standard CLI features
$ greet --help
Say hello in multiple languages

Usage: greet [options]

Options:
  --name, -n     Name to greet (required)
  --lang, -l     Language (en, ja, fr, es) (default: en)
  --help, -h     Show this help message
  --version, -v  Show version information
  --format       Output format (text|json) (default: text)

# Simple command invocation
$ greet -n "World" -l ja
Hello, World

# JSON output option
$ greet -n "World" -l ja --format=json
{
    "greeting": "Hello, World",
    "timestamp": 1699686400,
    "lang": "ja"
}

# Version information
$ greet --version
greet version 0.1.0

# Standard package management
$ brew upgrade my-project    # Upgrade to the latest version
$ brew uninstall my-project # Remove the package
```

BEAR.Cli provides:
- Native CLI command experience with standard features (--help, --version)
- Full Homebrew integration for easy distribution and updates
- No PHP or BEAR.Sunday knowledge required for end users
- Seamless integration with the Homebrew ecosystem

## Requirements

### For Development
- PHP 8.1+
- Composer

### For Homebrew Formula Generation (Optional)
- Git repository with GitHub remote URL
- Write permissions for formula directory

## Installation

Install via Composer:

    composer require bear/cli

## Usage

### 1. Add CLI Attributes to Resource

Add Cli attributes to define command interface:

```php
use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;

class Greeting extends ResourceObject
{
    #[Cli(
        name: 'greet',
        description: 'Say hello in multiple languages',
        output: 'greeting'
    )]
    public function onGet(
        #[Option(shortName: 'n', description: 'Name to greet')]
        string $name,
        #[Option(shortName: 'l', description: 'Language (en, ja, fr, es)')]
        string $lang = 'en'
    ): static {
        $this->body = [
            'greeting' => match($lang) {
                'ja' => "こんにちは, {$name}",
                'fr' => "Bonjour, {$name}",
                'es' => "¡Hola, {$name}",
                default => "Hello, {$name}"
            },
            'timestamp' => time(),
            'lang' => $lang
        ];
        return $this;
    }
}
```

### 2. Generate Commands

```bash
$ vendor/bin/bear-cli-gen MyVendor.MyProject
# CLI commands generated in /path/to/project/bin:
  greet

# If GitHub repository exists:
# Homebrew formula generated in /path/to/project/var/homebrew:
  greet.rb
```

## Distribution

BEAR.Cli offers two distribution methods:

### 1. Direct Usage (Always Available)
- Generated CLI commands in `bin/cli` directory
- Run directly from project directory
- Suitable for development and local use

### 2. Homebrew Distribution

BEAR.Cli generates Homebrew formulas that can be distributed in two ways:

#### A. Public Distribution via Tap

Package your command for the public Homebrew ecosystem:

1. Create a Homebrew tap repository:
```bash
# Repository name must be prefixed with 'homebrew-'
# Example: github.com/your-vendor/homebrew-greet for 'your-vendor/greet' tap
$ gh repo create your-vendor/homebrew-greet

# Clone and setup the repository
$ git clone git@github.com:your-vendor/homebrew-greet.git
$ cd homebrew-greet
```

2. Publish the formula:
```bash
# Copy the generated formula
$ cp ./var/homebrew/homebrew-greet/Fomula/greet.rb /path/to/homebrew-greet/Formula/
$ cd /path/to/homebrew-greet

# Commit and push
$ git add Formula/greet.rb
$ git commit -m "Add formula for greet command"
$ git push
```

3. Users can then install via Homebrew:
```bash
# Tap your formula repository
$ brew tap your-vendor/greet    # Note: 'homebrew-' prefix is omitted in tap command
$ brew install greet
```

#### B. Private/Local Distribution

For private projects or monorepos, you can install directly from the local formula file:

```bash
# Install from local formula
$ brew install --formula ./var/homebrew/greet.rb

# Or specify the full path
$ brew install --formula /path/to/project/var/homebrew/greet.rb
```

This approach is useful for:
- Private/internal tools
- Monorepo projects
- Development/testing before public release
- Bundling tools with other project files

#### Package Management Commands

Regardless of installation method, users can manage the package with standard Homebrew commands:
```bash
# Upgrade to the latest version
$ brew upgrade greet

# Remove the package
$ brew uninstall greet

# For tap-installed packages only:
$ brew untap your-vendor/greet
```

Note: The repository name must follow Homebrew's conventions:
- Repository must be named `homebrew-<tap-name>`
- Formula files should be placed in the `Formula` directory
- Tap will be referenced as `your-vendor/tap-name` (without 'homebrew-' prefix)
- The tap name typically matches your command name


## Configuration

### Command Output

The `output` parameter in `#[Cli]` specifies the response key for CLI output:

```php
#[Cli(
    description: 'Say hello in multiple languages',
    output: 'greeting'  # CLI will output this field's value
)]
```

Output behavior:
- Default: Displays only specified field's value
- `--format=json`: Full JSON response like the API endpoint
- Error messages go to stderr
- HTTP status codes map to exit codes (0: success, 1: client error, 2: server error)
