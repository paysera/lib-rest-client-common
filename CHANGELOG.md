# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

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
