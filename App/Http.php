<?php
namespace Yireo\Whoops\App;

/**
 * Class Http
 *
 * @package Yireo\Whoops\App
 */
class Http extends \Magento\Framework\App\Http
{
    /**
     * @param \Magento\Framework\App\Bootstrap $bootstrap
     * @param \Exception $exception
     *
     * @return bool
     */
    public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception)
    {
        if ($bootstrap->isDeveloperMode()) {

            $run = new \Whoops\Run;
            $handler = new \Whoops\Handler\PrettyPageHandler;


            $run->pushHandler($handler);
            $run->handleException($exception);
        }

        return parent::catchException($bootstrap, $exception);
    }
}