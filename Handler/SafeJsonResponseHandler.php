<?php declare(strict_types=1);

namespace Yireo\Whoops\Handler;

use Whoops\Exception\Formatter;
use Whoops\Handler\Handler;
use Whoops\Handler\JsonResponseHandler;

class SafeJsonResponseHandler extends JsonResponseHandler
{
    public function handle()
    {
        $error = Formatter::formatExceptionAsDataArray(
            $this->getInspector(),
            $this->addTraceToOutput(),
            $this->getRun()->getFrameFilters()
        );

        if (isset($error['trace']) && is_array($error['trace'])) {
            foreach (array_keys($error['trace']) as $index) {
                unset($error['trace'][$index]['args']);
            }
        }

        // phpcs:ignore Magento2.Security.LanguageConstruct.DirectOutput -- Whoops captures handler output via ob_start()
        echo json_encode(['error' => $error], JSON_PARTIAL_OUTPUT_ON_ERROR);

        return Handler::QUIT;
    }
}
