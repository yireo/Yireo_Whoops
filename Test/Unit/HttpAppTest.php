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

use Exception;
use Magento\Framework\App\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Http as HttpApp;
use Whoops\Handler\PrettyPageHandler as WhoopsPrettyPageHandler;
use Whoops\Run as WhoopsRunner;
use Yireo\Whoops\Plugin\HttpApp as HttpAppPlugin;
use Yireo\Whoops\Config\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class HttpAppTest
 *
 * @package Yireo\Whoops\Test\Unit
 */
class HttpAppTest extends TestCase
{
    /**
     * @test Make sure the plugin method is sane
     */
    public function testPluginReturnValueMatches()
    {
        $subject = $this->getHttpAppMock();
        $bootstrap = $this->getBootstrapMock(false);
        $exception = new Exception;

        $actual = $this->getHttpAppPlugin()->beforeCatchException($subject, $bootstrap, $exception);
        $this->assertSame($actual, [$bootstrap, $exception]);
    }

    /**
     * @test Make sure Whoops is not loaded before or after, when not in the Developer Mode
     */
    public function testWhoopsIsLoadedInDefaultMode()
    {
        $subject = $this->getHttpAppMock();
        $bootstrap = $this->getBootstrapMock(false);

        $this->assertFalse(in_array(WhoopsPrettyPageHandler::class, $this->getHandlerClassFromWhoops()));
        $this->getHttpAppPlugin()->beforeCatchException($subject, $bootstrap, new Exception);
        $this->assertFalse(in_array(WhoopsPrettyPageHandler::class, $this->getHandlerClassFromWhoops()));
    }

    /**
     * @test
     */
    public function testWhoopsIsLoadedInDeveloperMode()
    {
        $subject = $this->getHttpAppMock();
        $bootstrap = $this->getBootstrapMock(true);

        $this->assertFalse(in_array(WhoopsPrettyPageHandler::class, $this->getHandlerClassFromWhoops()));
        $this->getHttpAppPlugin()->beforeCatchException($subject, $bootstrap, new Exception);
        $this->assertTrue(in_array(WhoopsPrettyPageHandler::class, $this->getHandlerClassFromWhoops()));
    }

    /**
     * @return array
     */
    private function getHandlerClassFromWhoops(): array
    {
        $whoopsRunner = $this->getWhoopsRunner();
        $whoopsHandlers = $whoopsRunner->getHandlers();
        $currentHandlers = [];
        foreach ($whoopsHandlers as $whoopsHandler) {
            $currentHandlers[] = get_class($whoopsHandler);
        }

        return $currentHandlers;
    }

    /**
     * @return HttpApp
     */
    private function getHttpAppMock(): HttpApp
    {
        return $this->getMockBuilder(HttpApp::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return HttpAppPlugin
     */
    private function getHttpAppPlugin(): HttpAppPlugin
    {
        $whoopsRunner = $this->getWhoopsRunner();
        $prettyPageHandler = $this->getWhoopsPageHandler();
        $config = $this->getConfig();

        return new HttpAppPlugin($whoopsRunner, $prettyPageHandler, $config);
    }

    /**
     * @return WhoopsRunner
     */
    private function getWhoopsRunner(): WhoopsRunner
    {
        static $instance = false;
        if (!$instance) {
            $instance = new WhoopsRunner();
        }

        return $instance;
    }

    /**
     * @return WhoopsPrettyPageHandler
     */
    private function getWhoopsPageHandler(): WhoopsPrettyPageHandler
    {
        return new WhoopsPrettyPageHandler;
    }

    /**
     * @return Config
     */
    private function getConfig(): Config
    {
        $scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        return new Config($scopeConfig);
    }

    /**
     * @param bool $developerMode
     *
     * @return Bootstrap
     */
    private function getBootstrapMock(bool $developerMode = true): Bootstrap
    {
        $bootstrapMock = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $bootstrapMock->expects($this->once())->method('isDeveloperMode')->willReturn($developerMode);
        return $bootstrapMock;
    }
}
