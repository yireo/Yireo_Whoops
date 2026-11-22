<?php declare(strict_types=1);

namespace Yireo\Whoops\Exception;

use Exception;
use Throwable;

class WrappedError extends Exception
{
    public static function fromThrowable(Throwable $error): self
    {
        return new self($error->getMessage(), (int)$error->getCode(), $error);
    }
}
