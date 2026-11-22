<?php declare(strict_types=1);

namespace Yireo\Whoops\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Editor implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase|string}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('None')],
            ['value' => 'phpstorm', 'label' => 'PHPStorm'],
            ['value' => 'idea', 'label' => 'IDEA'],
            ['value' => 'vscode', 'label' => 'VSCode'],
            ['value' => 'cursor', 'label' => 'Cursor'],
            ['value' => 'sublime', 'label' => 'Sublime'],
            ['value' => 'atom', 'label' => 'Atom'],
            ['value' => 'emacs', 'label' => 'Emacs'],
            ['value' => 'macvim', 'label' => 'MacVim'],
            ['value' => 'netbeans', 'label' => 'NetBeans'],
            ['value' => 'textmate', 'label' => 'TextMate'],
            ['value' => 'espresso', 'label' => 'Espresso'],
            ['value' => 'xdebug', 'label' => __('Xdebug (xdebug.file_link_format)')],
        ];
    }
}
