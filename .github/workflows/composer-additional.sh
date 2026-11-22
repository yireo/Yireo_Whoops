# The Playwright tests need the fixture module Yireo_WhoopsTestFixture, which adds frontend pages that throw errors
case "${GITHUB_JOB:-$CI_JOB_NAME}" in playwright*)
  composer config repositories.local-source path /tmp/magento/package-source
  composer config repositories.local-source-fixture path /tmp/magento/package-source/Fixture/WhoopsTestFixture
  composer require -n -- yireo/magento2-whoops:@dev yireo/magento2-whoops-test-fixture:@dev
  ;;
esac
