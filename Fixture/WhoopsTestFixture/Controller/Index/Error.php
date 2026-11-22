<?php declare(strict_types=1);

namespace Yireo\WhoopsTestFixture\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Error implements HttpGetActionInterface
{
    public function execute()
    {
        throw new \Error('Whoops fixture error');
    }
}
