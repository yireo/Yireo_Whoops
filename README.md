# Magento 2 module for Whoops

This module adds [Whoops](https://filp.github.io/whoops/) error handling to Magento 2.

Requirements:
* Magento 2.0.0 Stable or higher

### Instructions for using composer
Use composer to install this extension. First make sure that Magento is installed via composer, and that there is a valid `composer.json` file present.

Next, install our module using the following command:

    composer require --dev yireo/magento2-whoops

Next, install the new module into Magento itself:

    ./bin/magento module:enable Yireo_Whoops
    ./bin/magento setup:upgrade

Check whether the module is succesfully installed in **Admin > Stores >
Configuration > Advanced > Advanced**.

Done.

### Instructions for manual copy
We recommend `composer` to install this package. However, if you want a manual copy instead, these are the steps:
* Upload the files in the `source/` folder to the folder `app/code/Yireo/Whoops` of your site
* Run `php -f bin/magento module:enable Yireo_Whoops`
* Run `php -f bin/magento setup:upgrade`
* Flush the Magento cache
* Done

## Overview
This module replaces the default error and exception handling of Magento 2, and replaces it with [Whoops](https://filp.github.io/whoops/).
If you want to know what Whoops is, check out their site.

## Usage
There is no usage. This module replaces the default `Magento\Framework\App\Http` class with its own and then adds in Whoops. This means that whenever an exception or error is not caught in the code, it will be outputted using Whoops.

You can test for this yourself by adding some dummy code somewhere in Magento 2:

    trigger_error('test');
    
or:

    throw new RuntimeException('test');

That's how we tested things.

## Testing
This repository contains a PHPUnit testing script, but it might not be evident to use this file. The basic usage is to run this script using a command like the following (where `MAGENTO` is your own Magento 2 installation):

    phpunit -c phpunit-yireo.xml --bootstrap MAGENTO/dev/tests/unit/framework/bootstrap.php
