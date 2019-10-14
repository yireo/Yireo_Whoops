<?php
namespace Yireo\Whoops\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Editor implements OptionSourceInterface
{

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '',
                'label' => 'None'
            ],
            [
                'value' => 'phpstorm',
                'label' => 'PHPStorm'
            ],
            [
                'value' => 'sublime',
                'label' => 'Sublime'
            ],
            [
                'value' => 'xdebug',
                'label' => 'XDebug'
            ],
            [
                'value' => 'vscode',
                'label' => 'VSCode'
            ],
            [
                'value' => 'emacs',
                'label' => 'emacs'
            ],
            [
                'value' => 'idea',
                'label' => 'IDEA'
            ],
            [
                'value' => 'macvim',
                'label' => 'MacVim'
            ],
            [
                'value' => 'textmate',
                'label' => 'Textmate'
            ],
            [
                'value' => 'atom',
                'label' => 'Atom'
            ],
            [
                'value' => 'espresso',
                'label' => 'Espresso'
            ]
        ];
    }
}