<?php declare(strict_types=1);

namespace Yireo\Whoops\Plugin;

use Closure;
use Exception;
use InvalidArgumentException;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\State;
use Psr\Log\LoggerInterface;
use Throwable;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as WhoopsRunner;
use Yireo\Whoops\Config\Config;
use Yireo\Whoops\Exception\WrappedError;

class HttpApp
{
    /**
     * @param array<string, string[]> $hiddenSuperglobalKeys
     */
    public function __construct(
        private readonly WhoopsRunner $whoopsRunner,
        private readonly PrettyPageHandler $pageHandler,
        private readonly JsonResponseHandler $jsonHandler,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        private readonly State $appState,
        private readonly HttpRequest $request,
        private readonly array $hiddenSuperglobalKeys = []
    ) {
    }

    /**
     * @return array{0: Bootstrap, 1: Exception}
     */
    public function beforeCatchException(Http $subject, Bootstrap $bootstrap, Exception $exception): array
    {
        if (!$bootstrap->isDeveloperMode()) {
            return [$bootstrap, $exception];
        }

        $throwable = $exception instanceof WrappedError && $exception->getPrevious()
            ? $exception->getPrevious()
            : $exception;

        $this->logException($throwable);
        $this->whoopsRunner->clearHandlers();
        $this->whoopsRunner->pushHandler($this->getHandler());
        $this->whoopsRunner->handleException($throwable);

        return [$bootstrap, $exception];
    }

    /**
     * @throws Throwable
     */
    public function aroundLaunch(Http $subject, Closure $proceed): ResponseInterface
    {
        try {
            return $proceed();
        } catch (Throwable $throwable) {
            if ($throwable instanceof Exception || !$this->isDeveloperMode()) {
                throw $throwable;
            }

            throw WrappedError::fromThrowable($throwable);
        }
    }

    private function getHandler(): HandlerInterface
    {
        if ($this->isJsonRequest()) {
            $this->jsonHandler->addTraceToOutput(true);
            return $this->jsonHandler;
        }

        $this->setEditor();
        $this->hideSuperglobalKeys();
        $this->addCustomViewsFolderToResourcePaths();

        return $this->pageHandler;
    }

    private function isJsonRequest(): bool
    {
        try {
            if ($this->request->isAjax()) {
                return true;
            }

            return str_contains((string)$this->request->getHeader('Accept'), 'application/json');
        } catch (Throwable) {
            return false;
        }
    }

    private function isDeveloperMode(): bool
    {
        try {
            return $this->appState->getMode() === State::MODE_DEVELOPER;
        } catch (Throwable) {
            return false;
        }
    }

    private function logException(Throwable $throwable): void
    {
        try {
            $this->logger->critical($throwable->getMessage(), ['exception' => $throwable]);
        } catch (Throwable) {
            return;
        }
    }

    private function setEditor(): void
    {
        $editor = $this->config->getEditor();
        if ($editor === '') {
            return;
        }

        try {
            $this->pageHandler->setEditor($editor);
        } catch (InvalidArgumentException) {
            return;
        }
    }

    private function hideSuperglobalKeys(): void
    {
        foreach ($this->hiddenSuperglobalKeys as $superglobalName => $keys) {
            foreach ($keys as $key) {
                $this->pageHandler->hideSuperglobalKey($superglobalName, $key);
            }
        }
    }

    private function addCustomViewsFolderToResourcePaths(): void
    {
        $viewsDirPath = __DIR__ . '/../view/whoops';
        if (is_dir($viewsDirPath)) {
            $this->pageHandler->addResourcePath($viewsDirPath);
        }
    }
}
