# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 2.7.0
### Changed
- Minimal version of PHP is increased to `7.4`.

### Fixed
- Deprecation error in the RequestException class (occurs in PHP 8+ only) was fixed

### Development tools
- PHPUnit version is narrowed to `^9.0`.
- Docker wrapper added

## 2.6.2
### Fixed
- Fixed some deprecation warning temporarily for PHP 8.1.

## 2.6.1
### Fixed
- Changed a "moved" function to work properly with `guzzlehttp/psr7^2.0`.

## 2.6.0
### Changed
- Bumped `guzzlehttp/guzzle` to `^7.0`.
- Bumped `phpunit/phpunit` to `^9.0|^10.0`.
- Bumped `guzzlehttp/psr7` to `^2.0`.

## 2.5.0
### Added
- Added `Entity/File` to represent a file.

## 2.4.2
### Fixed
- Throws `RuntimeException` when trying to create `RequestException` and response body is not seekable

## 2.4.1
### Fixed
- Basic authentication `Authorization` header must have prefix `Basic `

## 2.4.0
### Added
- Added ability to pass any configuration without whitelisting to the client
### Removed
- Removed `ConfigHandler::appendConfiguration`, instead options are appended with configurations in `ClientFactoryAbstract::createApiClient:65`

## 2.3.1
### Added
- If request body contains JSON, `Content-Type: application/json` will be set

## 2.3.0
### Added
- Library now accepts `headers`, `proxy` and `cookies` configuration.

## 2.2.0
### Added
- BearerAuthentication middleware added


## 2.1.0
### Added
- Placeholder in format `{name}` parsing supported in `base_url`. You should pass values to placeholders with `url_parameters` array to `options`.


## 2.0.2
### Changed
- `ResponseInterface $response` argument in `\Paysera\Component\RestClientCommon\Exception\RequestException::__construct` and 
`\Paysera\Component\RestClientCommon\Exception\RequestException::create` methods is now required.
`\Paysera\Component\RestClientCommon\Exception\RequestException::getResponse` method does not return null.


## 2.0.1
### Fixed
- Fixed travis configuration


## 2.0.0
### Changed
- `ApiClient` now requires 3rd argument as instance of `ClientFactoryAbstract`, 4th argument as options array.
- `ClientFactoryAbstract` class is now `abstract`.
- `ClientFactoryAbstract::OAUTH_BASE_URL` renamed to `AUTH_BASE_URL`.
- removed static methods from `ClientFactoryAbstract`, left only `::create` for BC. 
### Added
- `MacAuthentication` now supports key-value array of `parameters`. These will be encoded with `ext` data.
- `withOptions` support added to `ApiClient` class. Look for more info in readme.
- `auth_base_url` configuration parameter added.
