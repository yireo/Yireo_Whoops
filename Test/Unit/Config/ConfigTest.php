<?php declare(strict_types=1);

namespace Yireo\Whoops\Test\Unit\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\Whoops\Config\Config;
use Yireo\Whoops\Model\Config\Source\Editor;

class ConfigTest extends TestCase
{
    #[DataProvider('getEditorData')]
    public function testOnlyKnownEditorsAreReturned(mixed $value, string $expected): void
    {
        $this->assertSame($expected, $this->getConfig(['yireo_whoops/settings/editor' => $value])->getEditor());
    }

    /**
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function getEditorData(): array
    {
        return [
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
    }

    public function testFailingConfigReturnsNoEditor(): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willThrowException(new \RuntimeException('Database is down'));

        $this->assertSame('', (new Config($scopeConfig, new Editor()))->getEditor());
    }

    #[DataProvider('getAllowedIpsData')]
    public function testAllowedIpsAreParsed(mixed $value, array $expected): void
    {
        $this->assertSame($expected, $this->getConfig(['dev/restrict/allow_ips' => $value])->getAllowedIps());
    }

    /**
     * @return array<string, array{0: mixed, 1: string[]}>
     */
    public static function getAllowedIpsData(): array
    {
        return [
            'null' => [null, []],
            'empty' => ['', []],
            'whitespace' => ['  ', []],
            'single' => ['127.0.0.1', ['127.0.0.1']],
            'multiple with spaces' => [' 127.0.0.1 , 10.0.0.1,magento.test ', ['127.0.0.1', '10.0.0.1', 'magento.test']],
            'empty entries' => ['127.0.0.1,,', ['127.0.0.1']],
        ];
    }

    /**
     * @param array<string, mixed> $values
     */
    private function getConfig(array $values): Config
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(fn (string $path) => $values[$path] ?? null);

        return new Config($scopeConfig, new Editor());
    }
}
