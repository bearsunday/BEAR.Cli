<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\Cli\Fake\FakeResource;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ConfigTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $method = new ReflectionMethod(FakeResource::class, 'onGet');
        $this->config = new Config('app://self/greeting', $method);
    }

    public function testBasicConfig(): void
    {
        $this->assertSame('greeting', $this->config->name);
        $this->assertSame('Say hello in multiple languages', $this->config->description);
        $this->assertSame('0.1.0', $this->config->version);
        $this->assertSame('get', $this->config->method);
        $this->assertSame('app://self/greeting', $this->config->uri);
        $this->assertSame('greeting', $this->config->output);
    }

    public function testOptions(): void
    {
        $options = $this->config->options;
        $this->assertCount(2, $options);

        $nameOption = $options['name'];
        $this->assertSame('name', $nameOption->name);
        $this->assertSame('n', $nameOption->shortName);
        $this->assertSame('Name to greet', $nameOption->description);
        $this->assertSame('string', $nameOption->type);
        $this->assertTrue($nameOption->isRequired);
        $this->assertNull($nameOption->defaultValue);

        $langOption = $options['lang'];
        $this->assertSame('lang', $langOption->name);
        $this->assertSame('l', $langOption->shortName);
        $this->assertSame('Language (en, ja, fr, es)', $langOption->description);
        $this->assertSame('string', $langOption->type);
        $this->assertFalse($langOption->isRequired);
        $this->assertSame('en', $langOption->defaultValue);
    }

    public function testShortOptions(): void
    {
        $shortOpts = $this->config->shortOptions;
        $this->assertStringStartsWith('hv', $shortOpts);
        $this->assertStringContainsString('n:', $shortOpts); // required
        $this->assertStringContainsString('l::', $shortOpts); // optional
    }

    public function testLongOptions(): void
    {
        $longOpts = $this->config->longOptions;
        $this->assertContains('help', $longOpts);
        $this->assertContains('version', $longOpts);
        $this->assertContains('format::', $longOpts);
        $this->assertContains('name:', $longOpts);
        $this->assertContains('lang::', $longOpts);
    }

    public function testNoCliAttribute(): void
    {
        $this->expectException(Exception\LogicException::class);
        $this->expectExceptionMessage('No CLI attribute found');

        $method = new ReflectionMethod(FakeResource::class, 'noCliMethod');
        new Config('app://self/greeting', $method);
    }

    public function testMethodNameConversion(): void
    {
        $method = new ReflectionMethod(FakeResource::class, 'onPost');
        $config = new Config('app://self/greeting', $method);
        $this->assertSame('post', $config->method);
    }
}
