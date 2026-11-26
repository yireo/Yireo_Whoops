<?php declare(strict_types=1);
/**
 * Whoops plugin for Magento
 *
 * @package     Yireo_Whoops
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2016 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

namespace Yireo\Whoops\Plugin;

use Closure;
use Exception;
use InvalidArgumentException;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Throwable;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as WhoopsRunner;
use Yireo\Whoops\Config\Config;

class HttpApp
{
    /**
     * @var WhoopsRunner
     */
    private $whoopsRunner;

    /**
     * @var HandlerInterface
     */
    private $pageHandler;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var RemoteAddress|null
     */
    private $remoteAddress;

    /**
     * @var HttpRequest|null
     */
    private $request;

    /**
     * @var State|null
     */
    private $appState;

    /**
     * @var array<string, string[]>
     */
    private $hiddenSuperglobalKeys;

    /**
     * @var string
     */
    private $hiddenKeyPattern;

    /**
     * @param WhoopsRunner $whoopsRunner
     * @param HandlerInterface $pageHandler
     * @param Config $config
     * @param RemoteAddress|null $remoteAddress
     * @param HttpRequest|null $request
     * @param State|null $appState
     * @param array<string, string[]> $hiddenSuperglobalKeys
     * @param string $hiddenKeyPattern
     */
    public function __construct(
        WhoopsRunner $whoopsRunner,
        HandlerInterface $pageHandler,
        Config $config,
        ?RemoteAddress $remoteAddress = null,
        ?HttpRequest $request = null,
        ?State $appState = null,
        array $hiddenSuperglobalKeys = [],
        string $hiddenKeyPattern = ''
    ) {
        $this->whoopsRunner = $whoopsRunner;
        $this->pageHandler = $pageHandler;
        $this->config = $config;
        $this->remoteAddress = $remoteAddress;
        $this->request = $request;
        $this->appState = $appState;
        $this->hiddenSuperglobalKeys = $hiddenSuperglobalKeys;
        $this->hiddenKeyPattern = $hiddenKeyPattern;
    }

    /**
     * @param Http $subject
     * @param Bootstrap $bootstrap
     * @param Throwable $exception
     * @return array
     */
    public function beforeCatchException(
        Http $subject,
        Bootstrap $bootstrap,
        Throwable $exception
    ) {
        if (!$bootstrap->isDeveloperMode() || !$this->isClientAllowed()) {
            return [$bootstrap, $exception];
        }

        $this->setEditor();
        $this->hideSuperglobalKeys();
        $this->addCustomViewsFolderToResourcePaths();
        $this->whoopsRunner->pushHandler($this->pageHandler);
        $this->whoopsRunner->handleException($exception);

        return [$bootstrap, $exception];
    }

    /**
     * @param Http $subject
     * @param Closure $proceed
     * @return ResponseInterface
     * @throws Exception
     */
    public function aroundLaunch(Http $subject, Closure $proceed): ResponseInterface
    {
        if (!$this->isDeveloperMode()) {
            return $proceed();
        }

        try {
            return $proceed();
        } catch (Throwable $e) {
            if ($e instanceof Exception) {
                throw $e;
            }

            /**
             * Convert the Throwable to an exception for it to be caught by the main catch(\Exception) block.
             *
             * @see \Magento\Framework\App\Bootstrap::run
             */
            throw new Exception((string)$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Add custom resource path to allow us override Whoops resources
     *
     * @return void
     */
    public function addCustomViewsFolderToResourcePaths(): void
    {
        $viewsDirPath = __DIR__ . '/../view/whoops';

        if ($this->pageHandler instanceof PrettyPageHandler && is_dir($viewsDirPath)) {
            $this->pageHandler->addResourcePath($viewsDirPath);
        }
    }

    /**
     * @return bool
     */
    private function isClientAllowed(): bool
    {
        try {
            $allowedIps = $this->config->getAllowedIps();
            if ($allowedIps === []) {
                return true;
            }

            if ($this->remoteAddress === null || $this->request === null) {
                return false;
            }

            $remoteAddress = (string)$this->remoteAddress->getRemoteAddress();
            if ($remoteAddress !== '' && in_array($remoteAddress, $allowedIps, true)) {
                return true;
            }

            return in_array((string)$this->request->getHttpHost(), $allowedIps, true);
        } catch (Throwable $exception) {
            return false;
        }
    }

    /**
     * @return bool
     */
    private function isDeveloperMode(): bool
    {
        if ($this->appState === null) {
            return false;
        }

        try {
            return $this->appState->getMode() === State::MODE_DEVELOPER;
        } catch (Throwable $exception) {
            return false;
        }
    }

    /**
     * @return void
     */
    private function setEditor(): void
    {
        $editor = $this->config->getEditor();
        if ($editor === '' || !$this->pageHandler instanceof PrettyPageHandler) {
            return;
        }

        try {
            $this->pageHandler->setEditor($editor);
        } catch (InvalidArgumentException $exception) {
            return;
        }
    }

    /**
     * @return void
     */
    private function hideSuperglobalKeys(): void
    {
        if (!$this->pageHandler instanceof PrettyPageHandler) {
            return;
        }

        $hiddenKeys = array_merge_recursive($this->hiddenSuperglobalKeys, $this->getKeysMatchingHiddenPattern());
        foreach ($hiddenKeys as $superglobalName => $keys) {
            foreach ((array)$keys as $key) {
                $this->pageHandler->hideSuperglobalKey((string)$superglobalName, (string)$key);
            }
        }
    }

    /**
     * @return array<string, string[]>
     */
    private function getKeysMatchingHiddenPattern(): array
    {
        if ($this->hiddenKeyPattern === '' || $this->request === null) {
            return [];
        }

        $superglobals = [
            '_SERVER' => $this->request->getServer(),
            '_ENV' => $this->request->getEnv(),
        ];

        $matchingKeys = [];
        foreach ($superglobals as $superglobalName => $superglobal) {
            if (!is_object($superglobal) || !method_exists($superglobal, 'toArray')) {
                continue;
            }

            $keys = array_map('strval', array_keys($superglobal->toArray()));
            $matchingKeys[$superglobalName] = array_values(
                preg_grep($this->hiddenKeyPattern, $keys) ?: []
            );
        }

        return $matchingKeys;
    }
}
