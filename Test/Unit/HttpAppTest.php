<?php declare(strict_types=1);

namespace Yireo\Whoops\Test\Unit;

use Error;
use Exception;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http as HttpApp;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\State;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use TypeError;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as WhoopsRunner;
use Yireo\Whoops\Config\Config;
use Yireo\Whoops\Exception\WrappedError;
use Yireo\Whoops\Plugin\HttpApp as HttpAppPlugin;

class HttpAppTest extends TestCase
{
    private WhoopsRunner $whoopsRunner;
    private PrettyPageHandler $pageHandler;
    private JsonResponseHandler $jsonHandler;
    private LoggerInterface $logger;
    private ScopeConfigInterface $scopeConfig;
    private State $appState;
    private HttpRequest $request;

    protected function setUp(): void
    {
        $this->whoopsRunner = new WhoopsRunner();
        $this->whoopsRunner->allowQuit(false);
        $this->whoopsRunner->writeToOutput(false);
        $this->whoopsRunner->sendHttpCode(false);
        $this->pageHandler = new PrettyPageHandler();
        $this->jsonHandler = new JsonResponseHandler();
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $this->appState = $this->createStub(State::class);
        $this->request = $this->createStub(HttpRequest::class);
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
        $this->scopeConfig->method('getValue')->willReturn('notepad');

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([$this->pageHandler], $this->whoopsRunner->getHandlers());
    }

    public function testFailingConfigDoesNotStopWhoops(): void
    {
        $this->scopeConfig->method('getValue')->willThrowException(new RuntimeException('Database is down'));

        $this->getPlugin()->beforeCatchException($this->getSubject(), $this->getBootstrap(true), new Exception('test'));

        $this->assertSame([$this->pageHandler], $this->whoopsRunner->getHandlers());
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
    private function getPlugin(array $hiddenSuperglobalKeys = []): HttpAppPlugin
    {
        return new HttpAppPlugin(
            $this->whoopsRunner,
            $this->pageHandler,
            $this->jsonHandler,
            new Config($this->scopeConfig),
            $this->logger,
            $this->appState,
            $this->request,
            $hiddenSuperglobalKeys
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
