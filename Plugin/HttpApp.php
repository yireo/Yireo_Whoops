<?php declare(strict_types=1);
/**
 * Whoops plugin for Magento
 *
 * @package     Yireo_Whoops
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2016 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

namespace Yireo\Whoops\Plugin;

use Closure;
use Exception;
use Throwable;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;
use Magento\Framework\App\ResponseInterface;
use Whoops\Handler\HandlerInterface;
use Whoops\Run as WhoopsRunner;
use Yireo\Whoops\Config\Config;

/**
 * Class HttpApp - Plugin for \Magento\Framework\App\Http
 */
class HttpApp
{
    /**
     * @var WhoopsRunner
     */
    private $whoopsRunner;

    /**
     * @var HandlerInterface
     */
    private $pageHandler;

    /**
     * @var Config
     */
    private $config;

    /**
     * HttpApp constructor.
     * @param WhoopsRunner $whoopsRunner
     * @param HandlerInterface $pageHandler
     * @param Config $config
     */
    public function __construct(
        WhoopsRunner $whoopsRunner,
        HandlerInterface $pageHandler,
        Config $config
    ) {
        $this->whoopsRunner = $whoopsRunner;
        $this->pageHandler = $pageHandler;
        $this->config = $config;
    }

    /**
     * @param Http $subject
     * @param Bootstrap $bootstrap
     * @param Throwable $exception
     * @return array
     */
    public function beforeCatchException(
        Http $subject,
        Bootstrap $bootstrap,
        Throwable $exception
    ) {
        if ($bootstrap->isDeveloperMode() || $this->config->getOverride()) {
            $this->setEditor();
            $this->addCustomViewsFolderToResourcePaths();
            $this->whoopsRunner->pushHandler($this->pageHandler);
            $this->whoopsRunner->handleException($exception);
        }

        return [$bootstrap, $exception];
    }

    /**
     * @return bool
     */
    private function setEditor(): bool
    {
        if ($editor = $this->config->getEditor()) {
            $this->pageHandler->setEditor($editor);
            return true;
        }

        return false;
    }

    /**
     * @param Http $subject
     * @param Closure $proceed
     * @return ResponseInterface
     * @throws Exception
     */
    public function aroundLaunch(Http $subject, Closure $proceed): ResponseInterface
    {
        try {
            return $proceed();
        } catch (Throwable $e) {
            if ($e instanceof Exception) {
                throw $e;
            }

            /**
             * Convert the Throwable to an exception for it to be caught by the main catch(\Exception) block.
             *
             * @see \Magento\Framework\App\Bootstrap::run
             */
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Add custom resource path to allow us override Whoops resources
     *
     * @return void
     */
    public function addCustomViewsFolderToResourcePaths(): void
    {
        $viewsDirPath = __DIR__ . '/../view/whoops';

        if (is_dir($viewsDirPath)) {
            $this->pageHandler->addResourcePath($viewsDirPath);
        }
    }
}
