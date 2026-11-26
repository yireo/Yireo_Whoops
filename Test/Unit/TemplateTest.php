<?php declare(strict_types=1);

namespace Yireo\Whoops\Test\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as WhoopsRunner;

class TemplateTest extends TestCase
{
    public function testHeaderTemplateOnlyDiffersByKnownChanges(): void
    {
        $template = $this->getModuleTemplate('header.html.php');
        $template = $this->stripPhpDocHeader($template);
        $template = (string)preg_replace(
            '#\s*<li>\s*<a [^>]+site%3Amagento\.stackexchange\.com.*?</li>#s',
            '',
            $template
        );
        $template = str_replace('<?= ', '<?php echo ', $template);

        $this->assertSame($this->getVendorTemplate('header.html.php'), $template);
    }

    public function testFrameCodeTemplateOnlyDiffersByRemovedArguments(): void
    {
        $vendorTemplate = $this->getVendorTemplate('frame_code.html.php');
        $vendorTemplate = (string)preg_replace(
            '#\n\s*<\?php \$frameArgs = \$tpl->dumpArgs\(\$frame\); \?>.*?<\?php endif \?>\n#s',
            "\n",
            $vendorTemplate
        );

        $template = $this->stripPhpDocHeader($this->getModuleTemplate('frame_code.html.php'));
        $template = str_replace('<?= ', '<?php echo ', $template);

        $this->assertStringNotContainsString('dumpArgs', $template);
        $this->assertSame($vendorTemplate, $template);
    }

    public function testPageContainsNoFrameArguments(): void
    {
        ini_set('zend.exception_ignore_args', '0');
        $exception = $this->createException('secret-' . 'password');

        $handler = new PrettyPageHandler();
        $handler->addResourcePath(dirname(__DIR__, 2) . '/view/whoops');
        $handler->handleUnconditionally(true);

        $runner = new WhoopsRunner();
        $runner->allowQuit(false);
        $runner->writeToOutput(false);
        $runner->sendHttpCode(false);
        $runner->pushHandler($handler);

        $output = $runner->handleException($exception);

        $this->assertStringContainsString('exc-message', $output);
        $this->assertStringNotContainsString('id="frame-code-' . 'args-', $output);
        $this->assertStringNotContainsString('secret-' . 'password', $output);
    }

    private function createException(string $password): RuntimeException
    {
        try {
            $this->throwWithPassword($password);
        } catch (RuntimeException $exception) {
            return $exception;
        }

        $this->fail('No exception thrown');
    }

    private function throwWithPassword(string $password): void
    {
        throw new RuntimeException('test');
    }

    private function stripPhpDocHeader(string $template): string
    {
        return (string)preg_replace('#\A<\?php\ndeclare\(strict_types=1\);\n.*?\n\?>\n#s', '', $template);
    }

    private function getModuleTemplate(string $name): string
    {
        return (string)file_get_contents(dirname(__DIR__, 2) . '/view/whoops/views/' . $name);
    }

    private function getVendorTemplate(string $name): string
    {
        $whoopsDir = dirname((string)(new ReflectionClass(WhoopsRunner::class))->getFileName());
        $path = $whoopsDir . '/Resources/views/' . $name;
        if (!is_file($path)) {
            $this->markTestSkipped('Whoops template not found: ' . $path);
        }

        return (string)file_get_contents($path);
    }
}
