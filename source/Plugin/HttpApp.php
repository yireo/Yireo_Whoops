<?php
/**
 * Whoops plugin for Magento
 *
 * @package     Yireo_Whoops
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2016 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

namespace Yireo\Whoops\Plugin;

/**
 * Class HttpApp - Plugin for \Magento\Framework\App\Http
 */
class HttpApp
{
    public function beforeCatchException(
        \Magento\Framework\App\Http $subject,
        \Magento\Framework\App\Bootstrap $bootstrap,
        \Exception $exception
    )
    {
        if ($bootstrap->isDeveloperMode()) {

            $run = new \Whoops\Run;
            $handler = new \Whoops\Handler\PrettyPageHandler;
            $run->pushHandler($handler);

            $run->handleException($exception);
        }

        return [$bootstrap, $exception];
    }
}