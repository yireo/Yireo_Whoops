<?php declare(strict_types=1);

namespace Yireo\Whoops\Test\Integration\Plugin;

use Exception;
use Magento\Framework\App\Bootstrap as AppBootstrap;
use Magento\Framework\App\Http;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as WhoopsRunner;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertInterceptorPluginIsRegistered;
use Yireo\Whoops\Plugin\HttpApp as HttpAppPlugin;

class HttpAppTest extends TestCase
{
    use AssertInterceptorPluginIsRegistered;

    private WhoopsRunner $whoopsRunner;

    protected function setUp(): void
    {
        $this->whoopsRunner = new WhoopsRunner();
        $this->whoopsRunner->allowQuit(false);
        $this->whoopsRunner->writeToOutput(false);
        $this->whoopsRunner->sendHttpCode(false);
    }

    public function testPluginIsRegistered(): void
    {
        $this->assertInterceptorPluginIsRegistered(Http::class, HttpAppPlugin::class, 'Yireo_Whoops::HttpApp');
    }

    public function testWhoopsIsNotUsedOutsideDeveloperMode(): void
    {
        $this->getPlugin()->beforeCatchException(
            $this->createStub(Http::class),
            $this->getAppBootstrap(false),
            new Exception('Whoops integration test')
        );

        $this->assertSame([], $this->whoopsRunner->getHandlers());
    }

    public function testPrettyPageHandlerIsUsedInDeveloperModeAndHidesSensitiveKeys(): void
    {
        $this->getPlugin()->beforeCatchException(
            $this->createStub(Http::class),
            $this->getAppBootstrap(true),
            new Exception('Whoops integration test')
        );

        $handlers = $this->whoopsRunner->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(PrettyPageHandler::class, $handlers[0]);

        $masked = (new ReflectionMethod(PrettyPageHandler::class, 'masked'))->invoke(
            $handlers[0],
            ['HTTP_AUTHORIZATION' => 'Bearer secret', 'HTTP_COOKIE' => 'admin=secret', 'HTTP_HOST' => 'example.com'],
            '_SERVER'
        );

        $this->assertNotSame('Bearer secret', $masked['HTTP_AUTHORIZATION']);
        $this->assertNotSame('admin=secret', $masked['HTTP_COOKIE']);
        $this->assertSame('example.com', $masked['HTTP_HOST']);

        $maskedEnv = (new ReflectionMethod(PrettyPageHandler::class, 'masked'))->invoke(
            $handlers[0],
            ['HTTP_AUTHORIZATION' => 'Bearer secret', 'HTTP_COOKIE' => 'admin=secret'],
            '_ENV'
        );

        $this->assertNotSame('Bearer secret', $maskedEnv['HTTP_AUTHORIZATION']);
        $this->assertNotSame('admin=secret', $maskedEnv['HTTP_COOKIE']);
    }

    private function getPlugin(): HttpAppPlugin
    {
        return Bootstrap::getObjectManager()->create(HttpAppPlugin::class, [
            'whoopsRunner' => $this->whoopsRunner,
            'pageHandler' => Bootstrap::getObjectManager()->create(PrettyPageHandler::class),
        ]);
    }

    private function getAppBootstrap(bool $developerMode): AppBootstrap
    {
        $bootstrap = $this->createStub(AppBootstrap::class);
        $bootstrap->method('isDeveloperMode')->willReturn($developerMode);
        return $bootstrap;
    }
}
