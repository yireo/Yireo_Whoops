# Magento 2 module for Whoops

<!-- badges.specs.start -->
![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-whoops)
<!-- badges.specs.end -->

<img src="https://img.shields.io/packagist/dt/yireo/magento2-whoops"/> <img src="https://img.shields.io/packagist/v/yireo/magento2-whoops"/> <img src="https://img.shields.io/github/languages/top/yireo/Yireo_Whoops"/> <img src="https://img.shields.io/github/last-commit/yireo/Yireo_Whoops" /> <img src="https://img.shields.io/github/sponsors/yireo"/> <img src="https://img.shields.io/twitter/follow/yireo?style=social" />

This module adds [Whoops](https://filp.github.io/whoops/) error handling to Magento 2.

## Installation
Use composer to install this extension. First make sure that Magento is installed via composer, and that there is a valid `composer.json` file present.

Next, install our module using the following command:

```bash
composer require --dev yireo/magento2-whoops
```

Next, install the new module into Magento itself:

```bash
./bin/magento module:enable Yireo_Whoops
./bin/magento setup:upgrade
```

Check whether the module is successfully installed in **Admin > Stores > Configuration > Yireo > Yireo Whoops**.

### Manual copy
We recommend `composer` to install this package. However, if you want a manual copy instead, these are the steps:
* Upload the files in the `source/` folder to the folder `app/code/Yireo/Whoops` of your site
* Run `php -f bin/magento module:enable Yireo_Whoops`
* Run `php -f bin/magento setup:upgrade`
* Flush the Magento cache

## Usage
This module replaces the default error and exception handling of Magento 2 with [Whoops](https://filp.github.io/whoops/). If you want to know what Whoops is, check out their site. There is no further usage. This module replaces the default `Magento\Framework\App\Http` class with its own and then adds in Whoops. This means that whenever an exception or error is not caught in the code, it will be output using Whoops.

You can test this yourself by adding some dummy code somewhere in Magento 2:

```php
trigger_error('test');
```

or:

```php
throw new RuntimeException('test');
```

## Testing
This repository contains some PHPUnit tests:

- Make sure Magento is installed with composer packages in `vendor/` (aka via `composer install`)
- Next, navigate to the repository of this extension
- Execute the following command, replacing `PATH_TO_MAGENTO` with the actual path to Magento:

```bash
phpunit -c dev/tests/unit/phpunit.xml --bootstrap PATH_TO_MAGENTO/vendor/autoload.php Test/Unit
```

## PWA compatibility
This frontend extension will be completely replaced by React or Vue functionality, and therefore, this extension will not be made compatible with PWA.

## Current status

<!-- badges.test.start -->
![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_Whoops/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_Whoops/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_Whoops/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_Whoops/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_Whoops/compile.yml?label=compile)
<!-- badges.test.end -->
