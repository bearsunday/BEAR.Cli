<?php

declare(strict_types=1);

namespace BEAR\Cli\Fake;

use BEAR\Resource\ResourceObject;
use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;

class FakeErrorResource extends ResourceObject
{
    #[Cli(
        description: 'Resource that produces errors',
        output: 'message'
    )]
    public function onGet(
        #[Option(shortName: 'c', description: 'Status code')]
        int $code
    ): static {
        $this->code = $code;
        $this->body = [
            'message' => match($code) {
                400 => 'Bad Request',
                404 => 'Not Found',
                500 => 'Internal Server Error',
                default => 'Unknown Error'
            }
        ];

        return $this;
    }
}
