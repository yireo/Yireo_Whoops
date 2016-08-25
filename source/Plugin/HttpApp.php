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

use Exception;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http as MagentoHttp;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as WhoopsRun;

/**
 * Class HttpApp - Plugin for \Magento\Framework\App\Http
 */
class HttpApp
{
    /**
     * @param MagentoHttp $subject
     * @param Bootstrap $bootstrap
     * @param Exception $exception
     *
     * @return array
     */
    public function beforeCatchException(MagentoHttp $subject, Bootstrap $bootstrap, Exception $exception)
    {
        if ($bootstrap->isDeveloperMode()) {

            $run = new WhoopsRun;
            $handler = new PrettyPageHandler;
            $run->pushHandler($handler);

            $run->handleException($exception);
        }

        return [$bootstrap, $exception];
    }
}