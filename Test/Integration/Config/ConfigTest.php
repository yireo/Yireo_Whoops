<?php declare(strict_types=1);

namespace Yireo\Whoops\Test\Integration\Config;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Yireo\Whoops\Config\Config;

class ConfigTest extends TestCase
{
    public function testEditorIsEmptyByDefault(): void
    {
        $this->assertSame('', Bootstrap::getObjectManager()->get(Config::class)->getEditor());
    }

    /**
     * @magentoConfigFixture default/yireo_whoops/settings/editor phpstorm
     */
    public function testEditorIsReadFromConfig(): void
    {
        $this->assertSame('phpstorm', Bootstrap::getObjectManager()->get(Config::class)->getEditor());
    }
}
