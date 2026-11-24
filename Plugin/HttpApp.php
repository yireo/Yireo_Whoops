<?php declare(strict_types=1);

namespace Yireo\Whoops\Plugin;

use Closure;
use Exception;
use InvalidArgumentException;
use Laminas\Stdlib\ParametersInterface;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;
use Throwable;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as WhoopsRunner;
use Yireo\Whoops\Config\Config;
use Yireo\Whoops\Exception\WrappedError;
use Yireo\Whoops\Handler\SafeJsonResponseHandler;

class HttpApp
{
    /**
     * @param array<string, string[]> $hiddenSuperglobalKeys
     */
    public function __construct(
        private readonly WhoopsRunner $whoopsRunner,
        private readonly PrettyPageHandler $pageHandler,
        private readonly SafeJsonResponseHandler $jsonHandler,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        private readonly State $appState,
        private readonly HttpRequest $request,
        private readonly RemoteAddress $remoteAddress,
        private readonly array $hiddenSuperglobalKeys = [],
        private readonly string $hiddenKeyPattern = ''
    ) {
    }

    /**
     * @return array{0: Bootstrap, 1: Exception}
     */
    public function beforeCatchException(Http $subject, Bootstrap $bootstrap, Exception $exception): array
    {
        if (!$bootstrap->isDeveloperMode() || !$this->isClientAllowed()) {
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
        if (!$this->isDeveloperMode()) {
            return $proceed();
        }

        try {
            return $proceed();
        } catch (Throwable $throwable) {
            if ($throwable instanceof Exception) {
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

    private function isClientAllowed(): bool
    {
        try {
            $allowedIps = $this->config->getAllowedIps();
            if ($allowedIps === []) {
                return true;
            }

            $remoteAddress = (string)$this->remoteAddress->getRemoteAddress();
            if ($remoteAddress !== '' && in_array($remoteAddress, $allowedIps, true)) {
                return true;
            }

            return in_array((string)$this->request->getHttpHost(), $allowedIps, true);
        } catch (Throwable) {
            return false;
        }
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

        foreach ($this->getKeysMatchingHiddenPattern() as $superglobalName => $keys) {
            foreach ($keys as $key) {
                $this->pageHandler->hideSuperglobalKey($superglobalName, $key);
            }
        }
    }

    /**
     * @return array<string, string[]>
     */
    private function getKeysMatchingHiddenPattern(): array
    {
        if ($this->hiddenKeyPattern === '') {
            return [];
        }

        $superglobals = [
            '_SERVER' => $this->request->getServer(),
            '_ENV' => $this->request->getEnv(),
        ];

        $matchingKeys = [];
        foreach ($superglobals as $superglobalName => $superglobal) {
            if (!$superglobal instanceof ParametersInterface) {
                continue;
            }

            $keys = array_map('strval', array_keys($superglobal->toArray()));
            $matchingKeys[$superglobalName] = array_values(
                preg_grep($this->hiddenKeyPattern, $keys) ?: []
            );
        }

        return $matchingKeys;
    }

    private function addCustomViewsFolderToResourcePaths(): void
    {
        $viewsDirPath = __DIR__ . '/../view/whoops';
        if (is_dir($viewsDirPath)) {
            $this->pageHandler->addResourcePath($viewsDirPath);
        }
    }
}
