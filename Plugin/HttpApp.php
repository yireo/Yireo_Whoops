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

namespace Yireo\Whoops\Plugin;

use Throwable;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;
use Whoops\Handler\PrettyPageHandler;
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
     * @var PrettyPageHandler
     */
    private $pageHandler;

    /**
     * @var Config
     */
    private $config;

    /**
     * HttpApp constructor.
     * @param WhoopsRunner $whoopsRunner
     * @param PrettyPageHandler $pageHandler
     * @param Config $config
     */
    public function __construct(
        WhoopsRunner $whoopsRunner,
        PrettyPageHandler $pageHandler,
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
}
