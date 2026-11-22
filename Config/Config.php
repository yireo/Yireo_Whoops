<?php declare(strict_types=1);

namespace Yireo\Whoops\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Throwable;

class Config
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function getEditor(): string
    {
        try {
            return (string)$this->scopeConfig->getValue('yireo_whoops/settings/editor');
        } catch (Throwable) {
            return '';
        }
    }
}
