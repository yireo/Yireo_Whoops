<?php declare(strict_types=1);

namespace Yireo\WhoopsTestFixture\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\State;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Mode implements HttpGetActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly State $appState
    ) {
    }

    public function execute(): Json
    {
        return $this->jsonFactory->create()->setData(['mode' => $this->appState->getMode()]);
    }
}
