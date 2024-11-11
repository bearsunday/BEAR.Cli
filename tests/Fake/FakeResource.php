<?php

declare(strict_types=1);

namespace BEAR\Cli\Fake;

use BEAR\Resource\ResourceObject;
use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;

class FakeResource extends ResourceObject
{
    #[Cli(
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
}
