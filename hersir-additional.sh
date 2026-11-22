#!/bin/bash
# Install the fixture module Yireo_WhoopsTestFixture, needed by the Playwright tests (never in production)
set -e
MODULE_DIR=$(cd "$(dirname "$(readlink -f "$0")")" && pwd)

composer config repositories.yireo-whoops-test-fixture path "$MODULE_DIR/Fixture/WhoopsTestFixture"
composer show yireo/magento2-whoops-test-fixture >/dev/null 2>&1 \
  || composer require -n -- yireo/magento2-whoops-test-fixture:@dev
bin/magento module:enable Yireo_WhoopsTestFixture
