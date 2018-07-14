<?php
/**
 * Whoops plugin for Magento
 *
 * @package     Yireo_Whoops
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2016 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */
declare(strict_types=1);

namespace Yireo\Whoops\Test\Unit;

use Exception;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http as HttpApp;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Whoops\Handler\PrettyPageHandler as WhoopsPrettyPageHandler;
use Whoops\Run as WhoopsRunner;
use Yireo\Whoops\Plugin\HttpApp as HttpAppPlugin;

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

        $this->assertNotContains(WhoopsPrettyPageHandler::class, $this->getHandlerClassFromWhoops());
        $this->getHttpAppPlugin()->beforeCatchException($subject, $bootstrap, new Exception);
        $this->assertNotContains(WhoopsPrettyPageHandler::class, $this->getHandlerClassFromWhoops());
    }

    /**
     * @test
     */
    public function testWhoopsIsLoadedInDeveloperMode()
    {
        $subject = $this->getHttpAppMock();
        $bootstrap = $this->getBootstrapMock(true);

        $this->assertNotContains(WhoopsPrettyPageHandler::class, $this->getHandlerClassFromWhoops());
        $this->getHttpAppPlugin()->beforeCatchException($subject, $bootstrap, new Exception);
        $this->assertContains(WhoopsPrettyPageHandler::class, $this->getHandlerClassFromWhoops());
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

        return new HttpAppPlugin($whoopsRunner, $prettyPageHandler);
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
