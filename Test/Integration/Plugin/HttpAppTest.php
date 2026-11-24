<?php declare(strict_types=1);

namespace Yireo\Whoops\Test\Integration\Plugin;

use Exception;
use Magento\Framework\App\Bootstrap as AppBootstrap;
use Magento\Framework\App\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
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

    public function testDiConfigurationHidesCredentials(): void
    {
        $request = Bootstrap::getObjectManager()->get(HttpRequest::class);
        $request->getServer()->set('MYSQL_PASSWORD', 'secret');
        try {
            $this->getPlugin()->beforeCatchException(
                $this->createStub(Http::class),
                $this->getAppBootstrap(true),
                new Exception('Whoops integration test')
            );
        } finally {
            $request->getServer()->offsetUnset('MYSQL_PASSWORD');
        }

        $handler = $this->whoopsRunner->getHandlers()[0];
        $masked = new ReflectionMethod(PrettyPageHandler::class, 'masked');

        $server = $masked->invoke(
            $handler,
            ['MYSQL_PASSWORD' => 'secret', 'REDIRECT_HTTP_AUTHORIZATION' => 'Bearer secret', 'HTTP_HOST' => 'example.com'],
            '_SERVER'
        );
        $this->assertNotSame('secret', $server['MYSQL_PASSWORD']);
        $this->assertNotSame('Bearer secret', $server['REDIRECT_HTTP_AUTHORIZATION']);
        $this->assertSame('example.com', $server['HTTP_HOST']);

        $post = $masked->invoke(
            $handler,
            ['login' => ['username' => 'jane', 'password' => 'secret'], 'form_key' => 'abc', 'qty' => '1'],
            '_POST'
        );
        $this->assertSame('***', $post['login']);
        $this->assertNotSame('abc', $post['form_key']);
        $this->assertSame('1', $post['qty']);

        $session = $masked->invoke($handler, ['admin' => ['user' => 'admin']], '_SESSION');
        $this->assertSame('***', $session['admin']);
    }

    /**
     * @magentoConfigFixture default/dev/restrict/allow_ips 192.0.2.1
     */
    public function testWhoopsIsNotUsedForClientOutsideAllowedIps(): void
    {
        $this->getPlugin()->beforeCatchException(
            $this->createStub(Http::class),
            $this->getAppBootstrap(true),
            new Exception('Whoops integration test')
        );

        $this->assertSame([], $this->whoopsRunner->getHandlers());
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
