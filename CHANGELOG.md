# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

# 2.2.0
#Added
- BearerAuthentication middleware added


# 2.1.0
#Added
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
