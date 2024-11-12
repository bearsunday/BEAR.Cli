# BEAR.Cli

[![Continuous Integration](https://github.com/bearsunday/BEAR.Cli/workflows/Continuous%20Integration/badge.svg)](https://github.com/bearsunday/BEAR.Cli/actions)

Generate CLI commands from BEAR.Sunday resource methods.

## Overview

BEAR.Cli automatically creates CLI commands from your BEAR.Sunday resource methods using PHP attributes. It allows you to use the same resource as both a Web API and a CLI command.

In BEAR.Sunday, you can already access your resources via the page script:
```bash
$ php page.php '/greeting?name=World&lang=ja'
{
    "greeting": "こんにちは, World",
    "timestamp": 1699686400,
    "lang": "ja"
}
```

With BEAR.Cli, you can enhance these resources to be used as native CLI commands:
```bash
$ greet -n "World" -l ja
こんにちは, World
```

## Installation

### Composer install

    composer require bear/cli

## Usage

Add Cli attributes to your resource:

```php
use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;

class Greeting extends ResourceObject
{
    #[Cli(
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

Generate commands:
```bash
$ vendor/bin/bear-cli-gen MyVendor.MyProject
CLI commands have been generated in /Users/akihito/git/MyVendor.MyProject/bin:
  greet

Homebrew fomulas have been generated in /Users/akihito/git/MyVendor.MyProject/var/homebrew:
  greet.rb
```

Use the command:
```bash
# Regular use
$ bin/greet -n "World" -l ja
こんにちは, World

# Show help
$ bin/greet --help
Say hello in multiple languages

Usage: greet [options]

Options:
  --name, -n     Name to greet (required)
  --lang, -l     Language (en, ja, fr, es) (default: en)
  --help, -h     Show this help message
  --version, -v  Show version information
  --format       Output format (text|json) (default: text)

# JSON output
$ bin/greet -n "World" -l ja --format=json
{
    "greeting": "こんにちは, World",
    "timestamp": 1699686400,
    "lang": "ja"
}
```

### Homebrew Integration

The generated homebrew formula allows easy distribution:

```bash
# First time installation
$ brew tap your-vendor/my-project
$ brew install my-project

# Update
$ brew upgrade my-project
```

## Output

The `output` parameter in the Cli attribute specifies which response body key should be used for CLI output:

```php
#[Cli(
    description: 'Say hello in multiple languages',
    output: 'greeting'  // This key will be used for CLI output
)]
```

- Web API returns full response body as JSON
- CLI outputs only the specified key's value by default
- Use `--format=json` for full JSON response in CLI

## Exit Codes

Following UNIX conventions mapped to HTTP status:
- 0: Success (HTTP 200-399)
- 1: Client Error (HTTP 400-499)
- 2: Server Error (HTTP 500-599)

## Output Streams

- Normal output goes to stdout
- Error messages go to stderr
