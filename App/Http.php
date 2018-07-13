<?php

declare(strict_types=1);

namespace Yireo\Whoops\App;
use Exception;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http as CoreHttp;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * Class Http
 *
 * @package Yireo\Whoops\App
 */
class Http extends CoreHttp
{
    /**
     * @param Bootstrap $bootstrap
     * @param Exception $exception
     *
     * @return bool
     */
    public function catchException(Bootstrap $bootstrap, Exception $exception)
    {
        if ($bootstrap->isDeveloperMode()) {

            $run = new Run;
            $handler = new PrettyPageHandler;

            $run->pushHandler($handler);
            $run->handleException($exception);
        }

        return parent::catchException($bootstrap, $exception);
    }
}