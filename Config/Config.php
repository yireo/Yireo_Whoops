<?php declare(strict_types=1);

namespace Yireo\Whoops\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Yireo\Whoops\Model\Config\Source\Editor;

class Config
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Editor $editorSource
    ) {
    }

    public function getEditor(): string
    {
        try {
            $editor = (string)$this->scopeConfig->getValue('yireo_whoops/settings/editor');
        } catch (\Throwable) {
            return '';
        }

        $allowedEditors = array_column($this->editorSource->toOptionArray(), 'value');
        return in_array($editor, $allowedEditors, true) ? $editor : '';
    }

    /**
     * @return string[]
     */
    public function getAllowedIps(): array
    {
        $allowedIps = (string)$this->scopeConfig->getValue('dev/restrict/allow_ips');
        return preg_split('#\s*,\s*#', trim($allowedIps, " \f\n\r\t\v\x00"), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
