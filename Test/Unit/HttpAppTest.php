<?php declare(strict_types=1);

namespace Yireo\Whoops\Test\Unit;

use Error;
use Exception;
use Laminas\Stdlib\Parameters;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http as HttpApp;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use TypeError;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as WhoopsRunner;
use Yireo\Whoops\Config\Config;
use Yireo\Whoops\Exception\WrappedError;
use Yireo\Whoops\Handler\SafeJsonResponseHandler;
use Yireo\Whoops\Model\Config\Source\Editor;
use Yireo\Whoops\Plugin\HttpApp as HttpAppPlugin;

class HttpAppTest extends TestCase
{
    private WhoopsRunner $whoopsRunner;
    private PrettyPageHandler $pageHandler;
    private SafeJsonResponseHandler $jsonHandler;
    private LoggerInterface $logger;
    private ScopeConfigInterface $scopeConfig;
    private State $appState;
    private HttpRequest $request;
    private RemoteAddress $remoteAddress;

    protected function setUp(): void
    {
        $this->whoopsRunner = new WhoopsRunner();
        $this->whoopsRunner->allowQuit(false);
        $this->whoopsRunner->writeToOutput(false);
        $this->whoopsRunner->sendHttpCode(false);
        $this->pageHandler = new PrettyPageHandler();
        $this->jsonHandler = new SafeJsonResponseHandler();
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $this->appState = $this->createStub(State::class);
        $this->request = $this->createStub(HttpRequest::class);
        $this->remoteAddress = $this->createStub(RemoteAddress::class);
    }

    public function testReturnsOriginalArguments(): void
    {
        $bootstrap = $this->getBootstrap(true);
        $exception = new Exception('test');

        $actual = $this->getPlugin()->beforeCatchException($this->getSubject(), $bootstrap, $exception);

        $this->assertSame([$bootstrap, $exception], $actual);
    }

    public function testWhoopsIsNotUsedOutsideDeveloperMode(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->expects($this->never())->method('critical');

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(false), new Exception('test'));

        $this->assertSame([], $this->whoopsRunner->getHandlers());
    }

    public function testEditorConfigIsNotReadOutsideDeveloperMode(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->scopeConfig->expects($this->never())->method('getValue');

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(false), new Exception('test'));
    }

    public function testPrettyPageHandlerIsUsedInDeveloperMode(): void
    {
        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([$this->pageHandler], $this->whoopsRunner->getHandlers());
    }

    public function testHandlersDoNotPileUpWhenCalledTwice(): void
    {
        $plugin = $this->getPlugin();
        $plugin->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('first'));
        $plugin->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('second'));

        $this->assertCount(1, $this->whoopsRunner->getHandlers());
    }

    public function testJsonHandlerIsUsedForAjaxRequests(): void
    {
        $this->request->method('isAjax')->willReturn(true);

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([$this->jsonHandler], $this->whoopsRunner->getHandlers());
        $this->assertTrue($this->jsonHandler->addTraceToOutput());
    }

    public function testLaunchDoesNotCatchErrorsOutsideDeveloperMode(): void
    {
        $this->appState->method('getMode')->willReturn(State::MODE_DEFAULT);
        $error = new Error('test');

        try {
            $this->getPlugin()->aroundLaunch($this->getSubject(), fn () => throw $error);
            $this->fail('No error thrown');
        } catch (Error $caught) {
            $this->assertSame($error, $caught);
        }
    }

    public function testJsonHandlerIsUsedWhenJsonIsAccepted(): void
    {
        $this->request->method('isAjax')->willReturn(false);
        $this->request->method('getHeader')->willReturnMap([['Accept', false, 'application/json, text/plain']]);

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([$this->jsonHandler], $this->whoopsRunner->getHandlers());
    }

    public function testPrettyPageHandlerIsUsedWhenHtmlIsAccepted(): void
    {
        $this->request->method('isAjax')->willReturn(false);
        $this->request->method('getHeader')->willReturnMap([['Accept', false, 'text/html,application/xhtml+xml']]);

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([$this->pageHandler], $this->whoopsRunner->getHandlers());
    }

    public function testExceptionIsLoggedInDeveloperMode(): void
    {
        $exception = new Exception('logged message');
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->expects($this->once())
            ->method('critical')
            ->with('logged message', ['exception' => $exception]);

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), $exception);
    }

    public function testFailingLoggerDoesNotStopWhoops(): void
    {
        $this->logger->method('critical')->willThrowException(new RuntimeException('Disk full'));

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([$this->pageHandler], $this->whoopsRunner->getHandlers());
    }

    public function testWrappedErrorIsUnwrappedBeforeLogging(): void
    {
        $error = new TypeError('original type error');
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->expects($this->once())
            ->method('critical')
            ->with('original type error', ['exception' => $error]);

        $this->getPlugin()->beforeCatchException(
            $this->getSubject(),
            $this->getBootstrap(true),
            WrappedError::fromThrowable($error)
        );
    }

    public function testEditorIsSetFromConfig(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([['yireo_whoops/settings/editor', 'default', null, 'vscode']]);

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame('vscode://file/%2Ffoo.php:42', $this->pageHandler->getEditorHref('/foo.php', 42));
    }

    public function testUnknownEditorIsIgnored(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([['yireo_whoops/settings/editor', 'default', null, 'notepad']]);

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([$this->pageHandler], $this->whoopsRunner->getHandlers());
    }

    public function testFailingConfigDisablesWhoops(): void
    {
        $this->scopeConfig->method('getValue')->willThrowException(new RuntimeException('Database is down'));

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([], $this->whoopsRunner->getHandlers());
    }

    public function testCallableEditorIsIgnored(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([
            ['yireo_whoops/settings/editor', 'default', null, 'file_put_contents'],
        ]);

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertFalse($this->pageHandler->getEditorHref('/foo.php', 42));
    }

    public function testWhoopsIsUsedWhenNoIpsAreRestricted(): void
    {
        $this->remoteAddress->method('getRemoteAddress')->willReturn('203.0.113.1');

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([$this->pageHandler], $this->whoopsRunner->getHandlers());
    }

    public function testWhoopsIsUsedForAllowedIp(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([
            ['dev/restrict/allow_ips', 'default', null, '127.0.0.1, 203.0.113.1'],
        ]);
        $this->remoteAddress->method('getRemoteAddress')->willReturn('203.0.113.1');

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([$this->pageHandler], $this->whoopsRunner->getHandlers());
    }

    public function testWhoopsIsUsedForAllowedHost(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([
            ['dev/restrict/allow_ips', 'default', null, '127.0.0.1,magento.test'],
        ]);
        $this->remoteAddress->method('getRemoteAddress')->willReturn('203.0.113.1');
        $this->request->method('getHttpHost')->willReturn('magento.test');

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([$this->pageHandler], $this->whoopsRunner->getHandlers());
    }

    public function testWhoopsIsNotUsedForOtherIp(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([
            ['dev/restrict/allow_ips', 'default', null, '127.0.0.1'],
        ]);
        $this->remoteAddress->method('getRemoteAddress')->willReturn('203.0.113.1');
        $this->request->method('getHttpHost')->willReturn('magento.test');

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([], $this->whoopsRunner->getHandlers());
    }

    public function testWhoopsIsNotUsedWithoutRemoteAddressWhenIpsAreRestricted(): void
    {
        $this->scopeConfig->method('getValue')->willReturnMap([
            ['dev/restrict/allow_ips', 'default', null, '127.0.0.1'],
        ]);
        $this->remoteAddress->method('getRemoteAddress')->willReturn(false);

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([], $this->whoopsRunner->getHandlers());
    }

    public function testSuperglobalKeysAreHidden(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer secret';
        $plugin = $this->getPlugin(['_SERVER' => ['HTTP_AUTHORIZATION']]);
        $plugin->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $method = new \ReflectionMethod(PrettyPageHandler::class, 'masked');
        $masked = $method->invoke($this->pageHandler, ['HTTP_AUTHORIZATION' => 'Bearer secret'], '_SERVER');

        $this->assertNotSame('Bearer secret', $masked['HTTP_AUTHORIZATION']);
    }

    #[DataProvider('getHiddenKeyPatternData')]
    public function testKeysMatchingPatternAreHidden(string $key, bool $hidden): void
    {
        $this->request->method('getServer')->willReturn(new Parameters([$key => 'value']));
        $this->request->method('getEnv')->willReturn(new Parameters([$key => 'value']));
        $plugin = $this->getPlugin([], '/PASS|SECRET|TOKEN|_KEY$|^KEY|AUTH|CREDENTIAL|DSN|PRIVATE|DATABASE_URL/i');
        $plugin->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $method = new \ReflectionMethod(PrettyPageHandler::class, 'masked');
        foreach (['_SERVER', '_ENV'] as $superglobalName) {
            $masked = $method->invoke($this->pageHandler, [$key => 'value'], $superglobalName);
            $this->assertSame($hidden ? '*****' : 'value', $masked[$key], $superglobalName . ' ' . $key);
        }
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function getHiddenKeyPatternData(): array
    {
        return [
            'password' => ['MYSQL_PASSWORD', true],
            'secret' => ['APP_SECRET', true],
            'token' => ['GITHUB_TOKEN', true],
            'key suffix' => ['MAGENTO_CRYPT_KEY', true],
            'key prefix' => ['KEY_FILE', true],
            'auth' => ['REDIRECT_HTTP_AUTHORIZATION', true],
            'credential' => ['AWS_CREDENTIALS_FILE', true],
            'dsn' => ['SENTRY_DSN', true],
            'private' => ['PRIVATE_REPO_LOGIN', true],
            'database url' => ['DATABASE_URL', true],
            'lowercase' => ['db_password', true],
            'host' => ['HTTP_HOST', false],
            'keyboard' => ['MONKEYBOARD', false],
            'path' => ['PATH', false],
        ];
    }

    public function testLaunchResultIsReturned(): void
    {
        $response = $this->createStub(ResponseInterface::class);

        $actual = $this->getPlugin()->aroundLaunch($this->getSubject(), fn () => $response);

        $this->assertSame($response, $actual);
    }

    public function testLaunchRethrowsExceptions(): void
    {
        $this->appState->method('getMode')->willReturn(State::MODE_DEVELOPER);
        $exception = new RuntimeException('test');

        try {
            $this->getPlugin()->aroundLaunch($this->getSubject(), fn () => throw $exception);
            $this->fail('No exception thrown');
        } catch (RuntimeException $caught) {
            $this->assertSame($exception, $caught);
        }
    }

    public function testLaunchWrapsErrorsInDeveloperMode(): void
    {
        $this->appState->method('getMode')->willReturn(State::MODE_DEVELOPER);
        $error = new Error('test');

        try {
            $this->getPlugin()->aroundLaunch($this->getSubject(), fn () => throw $error);
            $this->fail('No exception thrown');
        } catch (WrappedError $caught) {
            $this->assertSame($error, $caught->getPrevious());
            $this->assertSame('test', $caught->getMessage());
        }
    }

    public function testLaunchDoesNotWrapErrorsInProductionMode(): void
    {
        $this->appState->method('getMode')->willReturn(State::MODE_PRODUCTION);
        $error = new Error('test');

        try {
            $this->getPlugin()->aroundLaunch($this->getSubject(), fn () => throw $error);
            $this->fail('No error thrown');
        } catch (Error $caught) {
            $this->assertSame($error, $caught);
        }
    }

    public function testLaunchDoesNotWrapErrorsWhenModeIsUnknown(): void
    {
        $this->appState->method('getMode')->willThrowException(new RuntimeException('No deployment config'));
        $error = new Error('test');

        try {
            $this->getPlugin()->aroundLaunch($this->getSubject(), fn () => throw $error);
            $this->fail('No error thrown');
        } catch (Error $caught) {
            $this->assertSame($error, $caught);
        }
    }

    /**
     * @param array<string, string[]> $hiddenSuperglobalKeys
     */
    private function getPlugin(array $hiddenSuperglobalKeys = [], string $hiddenKeyPattern = ''): HttpAppPlugin
    {
        return new HttpAppPlugin(
            $this->whoopsRunner,
            $this->pageHandler,
            $this->jsonHandler,
            new Config($this->scopeConfig, new Editor()),
            $this->logger,
            $this->appState,
            $this->request,
            $this->remoteAddress,
            $hiddenSuperglobalKeys,
            $hiddenKeyPattern
        );
    }

    private function getSubject(): HttpApp
    {
        return $this->createStub(HttpApp::class);
    }

    private function getBootstrap(bool $developerMode): Bootstrap
    {
        $bootstrap = $this->createStub(Bootstrap::class);
        $bootstrap->method('isDeveloperMode')->willReturn($developerMode);
        return $bootstrap;
    }
}
