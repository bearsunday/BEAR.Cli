<?php

namespace BEAR\Cli;

use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use Exception;

final class FakeStubResource implements ResourceInterface
{
    public function get(string $uri, array $query = []): never
    {
        throw new Exception('Unexpected error');
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

    public function uri($uri): RequestInterface
    {
    }

    public function newInstance($uri): ResourceObject
    {
        return new FakeNonStringOutputResource();
    }

    public function object(ResourceObject $ro): RequestInterface
    {
    }

    public function href(string $rel, array $query = []): ResourceObject
    {
        return new FakeNonStringOutputResource();
    }
};
