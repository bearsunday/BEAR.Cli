<?php

declare(strict_types=1);

namespace BEAR\Cli;

use BEAR\AppMeta\Meta;
use PHPUnit\Framework\TestCase;

use function dirname;
use function is_executable;
use function sys_get_temp_dir;

class CompileScriptTest extends TestCase
{
    private CompileScript $compiler;
    private Meta $meta;

    protected function setUp(): void
    {
        $this->compiler = new CompileScript(new GenScript());
        $this->meta = new Meta('FakeVendor\FakeProject', 'app', dirname(__DIR__) . '/tests/Fake/app', sys_get_temp_dir());
    }

    public function testCompile(): void
    {
        $sources = $this->compiler->compile($this->meta);

        $this->assertCount(3, $sources); // onGet, onPost from FakeResource + onGet from FakeErrorResource

        // 各ソースの検証
        $greetingSource = $this->findSourceByName($sources, 'greeting');
        $this->assertNotNull($greetingSource);
        $this->assertStringContainsString('app://self/fake-resource', $greetingSource->code);
        $binFile = $this->meta->appDir . '/bin/greeting';
        $this->assertFileExists($binFile);
        $this->assertTrue(is_executable($binFile));

        $postGreetingSource = $this->findSourceByName($sources, 'post-greeting');
        $this->assertNotNull($postGreetingSource);
        $this->assertStringContainsString('app://self/fake-resource', $postGreetingSource->code);
        $binFile = $this->meta->appDir . '/bin/post-greeting';
        $this->assertFileExists($binFile);
        $this->assertTrue(is_executable($binFile));

        $errorSource = $this->findSourceByName($sources, 'error');
        $this->assertNotNull($errorSource);
        $this->assertStringContainsString('app://self/fake-error-resource', $errorSource->code);
        $binFile = $this->meta->appDir . '/bin/error';
        $this->assertFileExists($binFile);
        $this->assertTrue(is_executable($binFile));
    }

    private function findSourceByName(array $sources, string $name): CommandSource|null
    {
        foreach ($sources as $source) {
            if ($source->name === $name) {
                return $source;
            }
        }

        return null;
    }
}
