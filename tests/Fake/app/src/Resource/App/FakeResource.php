<?php

declare(strict_types=1);

namespace FakeVendor\FakeProject\Resource\App;

use BEAR\Resource\ResourceObject;
use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;

class FakeResource extends ResourceObject
{
    #[Cli(
        name: 'greeting',
        description: 'Say hello in multiple languages',
        output: 'greeting'
    )]
    public function onGet(
        #[Option(shortName: 'n', description: 'Name to greet')]
        string $name,
        #[Option(shortName: 'l', description: 'Language (en, ja, fr, es)')]
        ?string $lang = 'en'
    ): static {
        $this->body = [
            'greeting' => match($lang) {
                'ja' => "こんにちは, {$name}",
                'fr' => "Bonjour, {$name}",
                'es' => "¡Hola, {$name}",
                default => "Hello, {$name}"
            },
            'timestamp' => 1699686400,  // Fixed timestamp for testing
            'lang' => $lang
        ];

        return $this;
    }

    #[Cli(
        name: 'post-greeting',
        description: 'Post hello in multiple languages'
    )]
    public function onPost(): void
    {
    }
}
