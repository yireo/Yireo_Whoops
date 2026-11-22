<?php declare(strict_types=1);

namespace Yireo\WhoopsTestFixture\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use RuntimeException;

class Exception implements HttpGetActionInterface
{
    public function execute()
    {
        throw new RuntimeException('Whoops fixture exception');
    }
}
