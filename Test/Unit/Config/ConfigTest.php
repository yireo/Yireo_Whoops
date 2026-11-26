<?php declare(strict_types=1);

namespace Yireo\Whoops\Test\Unit\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yireo\Whoops\Config\Config;
use Yireo\Whoops\Model\Config\Source\Editor;

class ConfigTest extends TestCase
{
    public function testOnlyKnownEditorsAreReturned(): void
    {
        $data = [
            'known editor' => ['phpstorm', 'phpstorm'],
            'xdebug' => ['xdebug', 'xdebug'],
            'empty' => ['', ''],
            'null' => [null, ''],
            'unknown editor' => ['notepad', ''],
            'php function' => ['file_put_contents', ''],
            'shell function' => ['system', ''],
            'static method' => ['Foo::bar', ''],
            'case variant' => ['PHPSTORM', ''],
        ];

        foreach ($data as $label => [$value, $expected]) {
            $actual = $this->getConfig(['yireo_whoops/settings/editor' => $value])->getEditor();
            $this->assertSame($expected, $actual, $label);
        }
    }

    public function testFailingConfigReturnsNoEditor(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willThrowException(new RuntimeException('Database is down'));

        $this->assertSame('', (new Config($scopeConfig, new Editor()))->getEditor());
    }

    public function testAllowedIpsAreParsed(): void
    {
        $data = [
            'null' => [null, []],
            'empty' => ['', []],
            'whitespace' => ['  ', []],
            'single' => ['127.0.0.1', ['127.0.0.1']],
            'multiple with spaces' => [' 127.0.0.1 , 10.0.0.1,magento.test ', ['127.0.0.1', '10.0.0.1', 'magento.test']],
            'empty entries' => ['127.0.0.1,,', ['127.0.0.1']],
        ];

        foreach ($data as $label => [$value, $expected]) {
            $actual = $this->getConfig(['dev/restrict/allow_ips' => $value])->getAllowedIps();
            $this->assertSame($expected, $actual, $label);
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    private function getConfig(array $values): Config
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(function (string $path) use ($values) {
            return $values[$path] ?? null;
        });

        return new Config($scopeConfig, new Editor());
    }
}
