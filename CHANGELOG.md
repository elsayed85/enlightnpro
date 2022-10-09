# Release Notes

## [Unreleased](https://github.com/enlightn/enlightnpro/compare/v1.16.0...master)

## [v1.16.0 (2021-03-27)](https://github.com/enlightn/enlightnpro/compare/v1.15.0...v1.16.0)

### Changed
- Show route URIs instead of URLs in Telescope analyzers ([#19](https://github.com/enlightn/enlightnpro/pull/19))

## [v1.15.0 (2021-03-23)](https://github.com/enlightn/enlightnpro/compare/v1.14.2...v1.15.0)

### Added
- Add analyzer to check xdebug in production ([#18](https://github.com/enlightn/enlightnpro/pull/18))

## [v1.14.2 (2021-03-11)](https://github.com/enlightn/enlightnpro/compare/v1.14.1...v1.14.2)

### Changed
- Pass fingerprinting check if server doesn't expose version ([394a596](https://github.com/enlightn/enlightnpro/commit/394a59602483cd7598ea8113ddbeaaa045be8bc3))

## [v1.14.1 (2021-03-11)](https://github.com/enlightn/enlightnpro/compare/v1.14.0...v1.14.1)

### Changed
- Revert bump in Enlightn version ([4cc254f](https://github.com/enlightn/enlightnpro/commit/4cc254f3999a615249cfeb8dc443922fff1dd2fa))

## [v1.14.0 (2021-03-11)](https://github.com/enlightn/enlightnpro/compare/v1.13.0...v1.14.0)

### Added
- Add new analyzer to detect server fingerprinting ([#17](https://github.com/enlightn/enlightnpro/pull/17))

## [v1.13.0 (2021-03-10)](https://github.com/enlightn/enlightnpro/compare/v1.12.1...v1.13.0)

### Added
- Add analyzer to check for http 2 protocol version ([#16](https://github.com/enlightn/enlightnpro/pull/16))

## [v1.12.1 (2021-03-06)](https://github.com/enlightn/enlightnpro/compare/v1.12.0...v1.12.1)

### Fixed
- Fix host injection checks by injecting headers one at a time ([#15](https://github.com/enlightn/enlightnpro/pull/15))

## [v1.12.0 (2021-03-06)](https://github.com/enlightn/enlightnpro/compare/v1.11.0...v1.12.0)

### Changed
- Improve detection of host injection ([#14](https://github.com/enlightn/enlightnpro/pull/14))

## [v1.11.0 (2021-02-28)](https://github.com/enlightn/enlightnpro/compare/v1.10.1...v1.11.0)

### Fixed
- Add configs for Github bot ([#13](https://github.com/enlightn/enlightnpro/pull/13))

## [v1.10.1 (2021-02-22)](https://github.com/enlightn/enlightnpro/compare/v1.10.0...v1.10.1)

### Fixed
- Fix bug when REDIS_URL is provided instead of the full config ([#11](https://github.com/enlightn/enlightnpro/pull/11))

## Added
- Add WTFPL to license whitelist ([#12](https://github.com/enlightn/enlightnpro/pull/12))

## [v1.10.0 (2021-02-10)](https://github.com/enlightn/enlightnpro/compare/v1.9.0...v1.10.0)

### Changed
- Relax class property check for mixed objects ([#10](https://github.com/enlightn/enlightnpro/pull/10))

## [v1.9.0 (2021-02-07)](https://github.com/enlightn/enlightnpro/compare/v1.8.0...v1.9.0)

### Added
- Update config values for new ignore_errors and baseline feature ([#9](https://github.com/enlightn/enlightnpro/pull/9))

## [v1.8.0 (2021-02-04)](https://github.com/enlightn/enlightnpro/compare/v1.7.0...v1.8.0)

### Added
- Add details to analyzer fail messages ([#8](https://github.com/enlightn/enlightnpro/pull/8))

## [v1.7.0 (2021-02-03)](https://github.com/enlightn/enlightnpro/compare/v1.6.0...v1.7.0)

### Added
- Support new CI mode in Enlightn Pro ([#7](https://github.com/enlightn/enlightnpro/pull/7))

## [v1.6.0 (2021-02-01)](https://github.com/enlightn/enlightnpro/compare/v1.5...v1.6.0)

### Added
- Improve security static analysis rules ([#6](https://github.com/enlightn/enlightnpro/pull/6))
- Enable faster tests with paratest ([#5](https://github.com/enlightn/enlightnpro/pull/5))

## [v1.5 (2021-01-27)](https://github.com/enlightn/enlightnpro/compare/v1.4...v1.5)

### Added
- Add analyzer to detect arbitrary file uploads ([#4](https://github.com/enlightn/enlightnpro/pull/4))

## [v1.4 (2021-01-26)](https://github.com/enlightn/enlightnpro/compare/v1.3...v1.4)

### Fixed
- Fix crash when there is a syntax error in one of the app files ([#3](https://github.com/enlightn/enlightnpro/pull/3))

## [v1.3 (2021-01-26)](https://github.com/enlightn/enlightnpro/compare/v1.2...v1.3)

### Added
- Add compact lines config and whitelisted license additions from Enlightn ([#2](https://github.com/enlightn/enlightnpro/pull/2))

## [v1.2 (2021-01-22)](https://github.com/enlightn/enlightnpro/compare/v1.1...v1.2)

### Added
- Add dont report config from Enlightn ([#1](https://github.com/enlightn/enlightnpro/pull/1))

## [v1.1 (2021-01-22)](https://github.com/enlightn/enlightnpro/compare/v1.0...v1.1)

### Fixed
- Add trinary maybe logic for PHPStan ([06cbd6a](https://github.com/enlightn/enlightnpro/commit/06cbd6a01f4caff7bd5971732c42d0f422a9d3e2))
