<?php

//declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Fake\FakeErrorResource;
use BEAR\Cli\Fake\FakeResource;
use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;

class FakeExceptionResource implements ResourceInterface
{
    public function get(string $uri, array $query = []): ResourceObject
    {
        $resource = new FakeNonStringOutputResource();
        $resource->body = ['output' => ['array', 'value']];

        return $resource;
    }

    public function put(string $uri, array $query = []): ResourceObject
    {
        return new FakeNonStringOutputResource();
    }

    public function post(string $uri, array $query = []): ResourceObject
    {
        return new FakeNonStringOutputResource();
    }

    public function delete(string $uri, array $query = []): ResourceObject
    {
        return new FakeNonStringOutputResource();
    }

    public function patch(string $uri, array $query = []): ResourceObject
    {
        return new FakeNonStringOutputResource();
    }

    public function head(string $uri, array $query = []): ResourceObject
    {
        return new FakeNonStringOutputResource();
    }

    public function options(string $uri, array $query = []): ResourceObject
    {
        return new FakeNonStringOutputResource();
    }

    public function uri($uri): RequestInterface // @phpstan-ignore-line
    {
    }

    public function newInstance($uri): ResourceObject
    {
        return new FakeNonStringOutputResource();
    }

    public function object(ResourceObject $ro): RequestInterface // @phpstan-ignore-line
    {
    }

    public function href(string $rel, array $query = []): ResourceObject
    {
        return new FakeNonStringOutputResource();
    }
}
