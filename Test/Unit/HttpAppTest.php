<?php declare(strict_types=1);
/**
 * Whoops plugin for Magento
 *
 * @package     Yireo_Whoops
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2016 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

namespace Yireo\Whoops\Test\Unit;

use Error;
use Exception;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http as HttpApp;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use PHPUnit\Framework\TestCase;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\PrettyPageHandler as WhoopsPrettyPageHandler;
use Whoops\Run as WhoopsRunner;
use Yireo\Whoops\Config\Config;
use Yireo\Whoops\Plugin\HttpApp as HttpAppPlugin;

class HttpAppTest extends TestCase
{
    /**
     * @var WhoopsRunner
     */
    private $whoopsRunner;

    protected function setUp(): void
    {
        $this->whoopsRunner = new WhoopsRunner();
        $this->whoopsRunner->allowQuit(false);
        $this->whoopsRunner->writeToOutput(false);
        $this->whoopsRunner->sendHttpCode(false);
    }

    public function testPluginReturnValueMatches(): void
    {
        $subject = $this->getHttpAppMock();
        $bootstrap = $this->getBootstrapMock(false);
        $exception = new Exception();

        $actual = $this->getHttpAppPlugin()->beforeCatchException($subject, $bootstrap, $exception);
        $this->assertSame([$bootstrap, $exception], $actual);
    }

    public function testWhoopsIsNotLoadedInDefaultMode(): void
    {
        $this->getHttpAppPlugin()->beforeCatchException($this->getHttpAppMock(), $this->getBootstrapMock(false), new Exception());
        $this->assertNotContains(WhoopsPrettyPageHandler::class, $this->getHandlerClassesFromWhoops());
    }

    public function testWhoopsIsLoadedInDeveloperMode(): void
    {
        $this->getHttpAppPlugin()->beforeCatchException($this->getHttpAppMock(), $this->getBootstrapMock(true), new Exception());
        $this->assertContains(WhoopsPrettyPageHandler::class, $this->getHandlerClassesFromWhoops());
    }

    public function testOverrideSettingIsIgnoredOutsideDeveloperMode(): void
    {
        $plugin = $this->getHttpAppPlugin(['yireo_whoops/settings/override' => '1']);
        $plugin->beforeCatchException($this->getHttpAppMock(), $this->getBootstrapMock(false), new Exception());
        $this->assertNotContains(WhoopsPrettyPageHandler::class, $this->getHandlerClassesFromWhoops());
    }

    public function testWhoopsIsNotLoadedForClientsOutsideAllowedIps(): void
    {
        $plugin = $this->getHttpAppPlugin(['dev/restrict/allow_ips' => '10.0.0.1, magento.test'], '192.168.1.1', 'example.com');
        $plugin->beforeCatchException($this->getHttpAppMock(), $this->getBootstrapMock(true), new Exception());
        $this->assertNotContains(WhoopsPrettyPageHandler::class, $this->getHandlerClassesFromWhoops());
    }

    public function testWhoopsIsLoadedForAllowedIp(): void
    {
        $plugin = $this->getHttpAppPlugin(['dev/restrict/allow_ips' => '10.0.0.1, 192.168.1.1'], '192.168.1.1', 'example.com');
        $plugin->beforeCatchException($this->getHttpAppMock(), $this->getBootstrapMock(true), new Exception());
        $this->assertContains(WhoopsPrettyPageHandler::class, $this->getHandlerClassesFromWhoops());
    }

    public function testWhoopsIsLoadedForAllowedHostname(): void
    {
        $plugin = $this->getHttpAppPlugin(['dev/restrict/allow_ips' => '10.0.0.1, magento.test'], '192.168.1.1', 'magento.test');
        $plugin->beforeCatchException($this->getHttpAppMock(), $this->getBootstrapMock(true), new Exception());
        $this->assertContains(WhoopsPrettyPageHandler::class, $this->getHandlerClassesFromWhoops());
    }

    public function testWhoopsIsNotLoadedWhenAllowedIpsAreSetButRemoteAddressIsUnknown(): void
    {
        $config = new Config($this->getScopeConfig(['dev/restrict/allow_ips' => '10.0.0.1']));
        $plugin = new HttpAppPlugin($this->whoopsRunner, new WhoopsPrettyPageHandler(), $config);
        $plugin->beforeCatchException($this->getHttpAppMock(), $this->getBootstrapMock(true), new Exception());
        $this->assertNotContains(WhoopsPrettyPageHandler::class, $this->getHandlerClassesFromWhoops());
    }

    public function testSensitiveKeysAreHidden(): void
    {
        $server = new class {
            public function toArray(): array
            {
                return ['DB_PASSWORD' => 'x', 'HTTP_HOST' => 'magento.test', 'APP_SECRET' => 'y'];
            }
        };

        $request = $this->createMock(HttpRequest::class);
        $request->method('getServer')->willReturn($server);

        $hiddenKeys = [];
        $pageHandler = $this->createMock(WhoopsPrettyPageHandler::class);
        $pageHandler->method('hideSuperglobalKey')->willReturnCallback(
            function (string $superglobalName, string $key) use (&$hiddenKeys) {
                $hiddenKeys[] = $superglobalName . ':' . $key;
            }
        );

        $plugin = new HttpAppPlugin(
            $this->whoopsRunner,
            $pageHandler,
            new Config($this->getScopeConfig([])),
            $this->createMock(RemoteAddress::class),
            $request,
            $this->getStateMock(State::MODE_DEVELOPER),
            ['_POST' => ['password' => 'password'], '_COOKIE' => ['PHPSESSID' => 'PHPSESSID']],
            '/PASS|SECRET/i'
        );

        $plugin->beforeCatchException($this->getHttpAppMock(), $this->getBootstrapMock(true), new Exception());

        sort($hiddenKeys);
        $this->assertSame(['_COOKIE:PHPSESSID', '_POST:password', '_SERVER:APP_SECRET', '_SERVER:DB_PASSWORD'], $hiddenKeys);
    }

    public function testUnknownEditorIsNotPassedToWhoops(): void
    {
        $pageHandler = $this->createMock(WhoopsPrettyPageHandler::class);
        $pageHandler->expects($this->never())->method('setEditor');

        $plugin = new HttpAppPlugin(
            $this->whoopsRunner,
            $pageHandler,
            new Config($this->getScopeConfig(['yireo_whoops/settings/editor' => 'file_put_contents']))
        );

        $plugin->beforeCatchException($this->getHttpAppMock(), $this->getBootstrapMock(true), new Exception());
    }

    public function testErrorIsConvertedToExceptionInDeveloperMode(): void
    {
        $error = new Error('test');
        try {
            $this->getHttpAppPlugin([], '', '', State::MODE_DEVELOPER)->aroundLaunch(
                $this->getHttpAppMock(),
                function () use ($error) {
                    throw $error;
                }
            );
        } catch (Exception $exception) {
            $this->assertSame($error, $exception->getPrevious());
            return;
        }

        $this->fail('No exception thrown');
    }

    public function testErrorIsNotConvertedOutsideDeveloperMode(): void
    {
        $this->expectException(Error::class);
        $this->getHttpAppPlugin([], '', '', State::MODE_PRODUCTION)->aroundLaunch(
            $this->getHttpAppMock(),
            function () {
                throw new Error('test');
            }
        );
    }

    public function testLaunchReturnsResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $actual = $this->getHttpAppPlugin([], '', '', State::MODE_PRODUCTION)->aroundLaunch(
            $this->getHttpAppMock(),
            function () use ($response) {
                return $response;
            }
        );

        $this->assertSame($response, $actual);
    }

    /**
     * @return string[]
     */
    private function getHandlerClassesFromWhoops(): array
    {
        return array_map(function (HandlerInterface $handler) {
            return get_class($handler);
        }, $this->whoopsRunner->getHandlers());
    }

    private function getHttpAppMock(): HttpApp
    {
        return $this->getMockBuilder(HttpApp::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @param array<string, mixed> $configValues
     */
    private function getHttpAppPlugin(
        array $configValues = [],
        string $remoteAddress = '127.0.0.1',
        string $httpHost = 'localhost',
        string $mode = State::MODE_DEVELOPER
    ): HttpAppPlugin {
        $remoteAddressMock = $this->createMock(RemoteAddress::class);
        $remoteAddressMock->method('getRemoteAddress')->willReturn($remoteAddress);

        $request = $this->createMock(HttpRequest::class);
        $request->method('getHttpHost')->willReturn($httpHost);

        return new HttpAppPlugin(
            $this->whoopsRunner,
            new WhoopsPrettyPageHandler(),
            new Config($this->getScopeConfig($configValues)),
            $remoteAddressMock,
            $request,
            $this->getStateMock($mode)
        );
    }

    private function getStateMock(string $mode): State
    {
        $state = $this->createMock(State::class);
        $state->method('getMode')->willReturn($mode);
        return $state;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function getScopeConfig(array $values): ScopeConfigInterface
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(function (string $path) use ($values) {
            return $values[$path] ?? null;
        });

        return $scopeConfig;
    }

    private function getBootstrapMock(bool $developerMode = true): Bootstrap
    {
        $bootstrapMock = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $bootstrapMock->method('isDeveloperMode')->willReturn($developerMode);
        return $bootstrapMock;
    }
}
