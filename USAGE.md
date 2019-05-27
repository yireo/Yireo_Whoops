# Usage
This module replaces the default error and exception handling of Magento 2, and replaces it with [Whoops](https://filp.github.io/whoops/). If you want to know what Whoops is, check out their site. There is no usage. This module replaces the default `Magento\Framework\App\Http` class with its own and then adds in Whoops. This means that whenever an exception or error is not caught in the code, it will be outputted using Whoops.

You can test for this yourself by adding some dummy code somewhere in Magento 2:

    trigger_error('test');
    
or:

    throw new RuntimeException('test');

That's how we tested things.

