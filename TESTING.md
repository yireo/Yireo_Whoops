# Testing
This repository contains some PHPUnit tests:

- Make sure Magento is installed with composer packages in `vendor/` (aka via `composer install`)
- Next, navigate to the repository of this extension
- Execute the following command with `PATH_TO_MAGENTO` with the actual path to Magento

    phpunit -c dev/tests/unit/phpunit.xml --bootstrap PATH_TO_MAGENTO/vendor/autoload.php Test/Unit
