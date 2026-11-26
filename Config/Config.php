<?php declare(strict_types=1);

namespace Yireo\Whoops\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Throwable;
use Yireo\Whoops\Model\Config\Source\Editor;

class Config
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Editor
     */
    private $editorSource;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Editor|null $editorSource
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ?Editor $editorSource = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->editorSource = $editorSource ?? new Editor();
    }

    /**
     * @return string
     */
    public function getEditor(): string
    {
        try {
            $editor = (string)$this->scopeConfig->getValue('yireo_whoops/settings/editor');
        } catch (Throwable $exception) {
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

    /**
     * @return bool
     * @deprecated The production mode override exposed sensitive data and is ignored since 0.2.6
     */
    public function getOverride(): bool
    {
        return (bool)$this->scopeConfig->getValue('yireo_whoops/settings/override');
    }
}
