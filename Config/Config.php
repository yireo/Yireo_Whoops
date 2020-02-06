<?php
declare(strict_types=1);

namespace Yireo\Whoops\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 *
 * @package Yireo\Whoops\Config
 */
class Config
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getEditor(): string
    {
        return (string)$this->scopeConfig->getValue('yireo_whoops/settings/editor');
    }

    /**
     * @return bool
     */
    public function getOverride(): bool
    {
        return (bool)$this->scopeConfig->getValue('yireo_whoops/settings/override');
    }
}
