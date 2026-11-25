# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 25 November 2026
### Removed
- The "Override Production Mode error reporting" setting is gone: Whoops now only runs in developer mode, so production visitors never get full error pages with server data, environment and cookies (breaking)
- PHP 8.1 support dropped; now requires PHP 8.2-8.5, Magento 2.4 (framework 103) and filp/whoops 2.15 or newer

### Added
- PHP 8.5 support
- Cursor and NetBeans as editor options
- AJAX and JSON requests get a JSON error response instead of an HTML page
- Whoops also respects `dev/restrict/allow_ips` (IPs or hostnames) on top of developer mode

### Fixed
- Arbitrary PHP function execution via the editor setting: only editors from the source list are accepted
- Stack frame arguments are no longer exposed on the HTML page or in the JSON trace
- More credentials masked: HTTP authorization headers, passwords, payment and form_key POST data, session and persistent cookies, admin/customer/checkout session data and sensitive server/env keys
- Exceptions are logged before Whoops displays them
- PHP errors outside developer mode are no longer wrapped by Whoops
- Reading the editor setting no longer fails when the configuration cannot be loaded
- Error page header synced with Whoops 2.18, with the docref URL and previous exception codes escaped
- Missing ACL resource for the configuration section added

## [0.2.5] - 01 June 2026
- Remove `setup_version` from XML
- Properly set version of symfony/var-dumper
- Make sure exception message is a string, not a Phrase

## [0.2.4] - 01 June 2026
### Fixed
- Unknown

## [0.2.3] - 17 April 2025
### Fixed
- Move interface/class mapping from preference to type, due to 2.4.8

## [0.2.2] - 12 September 2024
### Fixed
- Actual commits

## [0.2.1] - 12 September 2024
### Fixed
- Added type hints to template

## [0.2.0] - 12 June 2022
### Added
- Add links to Google and StackExchange (@moElwan)

## [0.1.4] - 14 April 2022
### Removed
- Removed support for Magento 2.0, 2.1 and 2.2
- Removed PHP requirement (so that Magento its req is sufficient)

## [0.1.3] - 29 July 2020
### Added
- Magento 2.4 support

## [0.1.2] - 2020-04-07
### Added
- Convert Throwables to Exceptions (@DuckThom)
## [0.1.2] - 2020-04-07
### Added
- Convert Throwables to Exceptions (@DuckThom)

## [0.1.1] - 2020-02-06
### Added
- Production mode report override
- matching Magento and composer version


## [0.1.0] - 2019-10-14
### Added
- Setting for editor to open up links
- KeepAChangeLog standard

## [0.0.11] - 2018/2019
- See GitHub repository for release notes
