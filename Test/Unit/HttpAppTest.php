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
use Magento\Framework\App\Http as MagentoHttp;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use RuntimeException;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as WhoopsRun;
use Yireo\Whoops\Plugin\HttpApp;

/**
 * Class HttpAppTest
 *
 * @package Yireo\Whoops\Test\Unit
 */
class HttpAppTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var HttpApp
     */
    private $sut;

    /**
     * @var MagentoHttp
     */
    private $subjectMock;

    /**
     * @var Bootstrap
     */
    private $bootstrapMock;

    public function setUp()
    {
        parent::setUp();

        $this->objectManager = new ObjectManager($this);
        $this->subjectMock = $this->getMockBuilder(MagentoHttp::class)->disableOriginalConstructor()->getMock();
        $this->bootstrapMock = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $this->bootstrapMock->expects($this->once())->method('isDeveloperMode')->willReturn(true);
        $this->sut = $this->objectManager->getObject(HttpApp::class);
    }

    /**
     * @test
     */
    public function itShouldRunTheWhoopsHandler()
    {
        $exception = new Exception();
        $actual = $this->sut->beforeCatchException($this->subjectMock, $this->bootstrapMock, $exception);

        self::assertTrue(is_array($actual));

    }
}