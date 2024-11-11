<?php

//declare(strict_types=1);

namespace BEAR\Cli\Fake;

use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;

class FakeResourceFactory implements ResourceInterface
{
    /** @var array<string, FakeResource|FakeErrorResource> */
    private array $resources = [];

    public function __construct()
    {
        $this->resources = [
            'app://self/greeting' => new FakeResource(),
            'app://self/error' => new FakeErrorResource()
        ];
    }

    public function get(string $uri, array $query = []): ResourceObject
    {
        $ro = $this->resources[$uri];
        return $ro->onGet(...$query);

    }

    public function post(string $uri, array $query = []): ResourceObject
    {
        return $this->resources[$uri];
    }

    public function put(string $uri, array $query = []): ResourceObject
    {
        return $this->resources[$uri];
    }

    public function delete(string $uri, array $query = []): ResourceObject
    {
        return $this->resources[$uri];
    }

    public function patch(string $uri, array $query = []): ResourceObject
    {
        return $this->resources[$uri];
    }

    public function head(string $uri, array $query = []): ResourceObject
    {
        return $this->resources[$uri];
    }

    public function options(string $uri, array $query = []): ResourceObject
    {
        return $this->resources[$uri];
    }

    public function uri($uri): RequestInterface
    {
        return $this->resources[$uri];
    }

    public function newInstance($uri): ResourceObject
    {
        return $this->resources[$uri];
    }

    public function object(ResourceObject $ro): RequestInterface
    {
    }

    public function href(string $rel, array $query = []): ResourceObject
    {
    }
}
