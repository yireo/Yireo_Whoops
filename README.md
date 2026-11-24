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

## Usage
This module adds [Whoops](https://filp.github.io/whoops/) to the error handling of Magento 2, through a plugin on `Magento\Framework\App\Http`. Whenever an exception or PHP error is not caught in the code, it is shown with Whoops.

Whoops is only used in the **Developer Mode**. In the Default Mode and Production Mode, the regular Magento error handling is used, so no stack traces or server details are shown to visitors.

- Exceptions are still logged to `var/log/exception.log` before Whoops renders them;
- PHP errors (like a `TypeError`) are shown as the original error, not as a wrapping exception;
- AJAX requests and requests that accept `application/json` get a JSON response instead of an HTML page;
- When **Stores > Configuration > Advanced > Developer > Developer Client Restrictions > Allowed IPs** (`dev/restrict/allow_ips`) is set, Whoops is only shown to these IPs (or hostnames). Other visitors get the regular Magento error page;
- Sensitive values (like the `Authorization` header, the session cookies, login passwords and environment variables with names like `*PASSWORD*`, `*SECRET*`, `*TOKEN*` or `*_KEY`) are hidden on the Whoops page. Add more keys via the DI argument `hiddenSuperglobalKeys` of `Yireo\Whoops\Plugin\HttpApp`, or change the regular expression in the DI argument `hiddenKeyPattern`;
- The arguments of the functions in the stack trace are not shown, neither on the Whoops page nor in the JSON response, because they often contain passwords or other credentials;
- Only the editors listed in the configuration are accepted. Any other value of `yireo_whoops/settings/editor` is ignored;
- Once Whoops renders an error, it ends the request. Other plugins on `Magento\Framework\App\Http::catchException()` may not run;
- Pick the editor to open files with in **Stores > Configuration > Yireo > Yireo Whoops**.

You can test this yourself by adding some dummy code somewhere in Magento 2:

```php
throw new RuntimeException('test');
```

## Upgrading to 1.0.0
The setting **Override Production Mode error reporting** has been removed, because it showed full stack traces and server details to every visitor. Whoops is now only used in the Developer Mode.

## Testing
This repository contains PHPUnit unit tests (`Test/Unit`), PHPUnit integration tests (`Test/Integration`) and Playwright tests (`Test/Playwright`). The Playwright tests need the fixture module `Yireo_WhoopsTestFixture` (in `Fixture/WhoopsTestFixture`), which adds pages that throw errors. Never install this fixture module in production.

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
