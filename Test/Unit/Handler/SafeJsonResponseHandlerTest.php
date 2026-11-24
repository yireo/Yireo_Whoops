<?php declare(strict_types=1);

namespace Yireo\Whoops\Test\Unit\Handler;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Whoops\Run as WhoopsRunner;
use Yireo\Whoops\Handler\SafeJsonResponseHandler;

class SafeJsonResponseHandlerTest extends TestCase
{
    public function testTraceContainsNoArguments(): void
    {
        $data = $this->handle($this->createException('secret-' . 'password'), true);

        $this->assertSame('RuntimeException', $data['error']['type']);
        $this->assertSame('test', $data['error']['message']);
        $this->assertNotEmpty($data['error']['trace']);
        foreach ($data['error']['trace'] as $frame) {
            $this->assertArrayNotHasKey('args', $frame);
            $this->assertArrayHasKey('function', $frame);
        }
    }

    public function testTraceIsOmittedWhenDisabled(): void
    {
        $data = $this->handle($this->createException('secret-' . 'password'), false);

        $this->assertArrayNotHasKey('trace', $data['error']);
    }

    /**
     * @return array<string, mixed>
     */
    private function handle(RuntimeException $exception, bool $addTrace): array
    {
        $handler = new SafeJsonResponseHandler();
        $handler->addTraceToOutput($addTrace);

        $runner = new WhoopsRunner();
        $runner->allowQuit(false);
        $runner->writeToOutput(false);
        $runner->sendHttpCode(false);
        $runner->pushHandler($handler);

        $output = $runner->handleException($exception);
        $this->assertStringNotContainsString('secret-' . 'password', $output);

        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    }

    private function createException(string $password): RuntimeException
    {
        ini_set('zend.exception_ignore_args', '0');
        try {
            $this->throwWithPassword($password);
        } catch (RuntimeException $exception) {
            return $exception;
        }

        $this->fail('No exception thrown');
    }

    private function throwWithPassword(string $password): void
    {
        throw new RuntimeException('test');
    }
}
