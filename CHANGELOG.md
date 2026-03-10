# Changelog

All notable changes to this project will be documented in this file. See
[Conventional Commits](https://conventionalcommits.org) for commit guidelines.

## [1.3.2](https://github.com/WebProject-xyz/php-openapi-mock-server/compare/1.3.1...1.3.2) (2026-03-10)

### Bug Fixes

* **code-style:** used rector to cleanup and update code ([87b2033](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/87b20330c085317a998c3b4271abd6552164f4a6))
* **cs:** fix .php-cs-fixer config include paths ([b16c84a](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/b16c84a097bc30fc40784fe43a5dcc27a90bc1e6))
* **faker:** implement path parameter injection and resolve data generation issues ([4d6269d](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/4d6269d4901a22989c429d77eca7c84a4900fb6f))
* **faker:** resolve infinite loop and ensure non-empty responses ([80c3543](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/80c35437ca88f61a1d1b6b9c64ef9d38a198d62f))
* **ui:** resolve Swagger UI initialization and spec detection ([1f82315](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/1f82315d16053b81254df5c89389624147579069))

## [1.3.1](https://github.com/WebProject-xyz/php-openapi-mock-server/compare/1.3.0...1.3.1) (2026-03-10)

### Bug Fixes

* **docker:** add docker build ([2bd1e17](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/2bd1e173497973050b4f30eae796ad4a9b2e7a95))

## [1.3.0](https://github.com/WebProject-xyz/php-openapi-mock-server/compare/1.2.3...1.3.0) (2026-03-10)

### Features

* **caching:** optimize caching by using pid and tmp dir ([d276dee](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/d276deeaac120e775def5027226fcb283f966d79))

## [1.2.3](https://github.com/WebProject-xyz/php-openapi-mock-server/compare/1.2.2...1.2.3) (2026-03-10)

### Bug Fixes

* **mock-server:** handle 404 and return codes for get endpoints ([90ede6a](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/90ede6ad67f853039a8482fc3c20ad4f1525dc0e))

## [1.2.2](https://github.com/WebProject-xyz/php-openapi-mock-server/compare/1.2.1...1.2.2) (2026-03-10)

### Bug Fixes

* **mock-server:** fix mock response handling for 204 and 201 status codes ([f8c4056](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/f8c4056783afa721abd1b47d85f4fdb311008ecc))

## [1.2.1](https://github.com/WebProject-xyz/php-openapi-mock-server/compare/1.2.0...1.2.1) (2026-03-10)

### Bug Fixes

* **deps:** allow "webmozart/assert": "^1.12 || ^2.0" ([6f0a0c8](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/6f0a0c842c5e76422270a52f1bbf1a4cfdf4f110))

## [1.2.0](https://github.com/WebProject-xyz/php-openapi-mock-server/compare/1.1.0...1.2.0) (2026-03-10)

### Features

* allow symfony ^6.4 ([db64930](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/db64930b1db7c419425615e707cd3472820e15dd))

## [1.1.0](https://github.com/WebProject-xyz/php-openapi-mock-server/compare/1.0.0...1.1.0) (2026-03-10)

### Features

* support usage as Composer dependency (bin script, path resolution, Accept header) ([42968d0](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/42968d04c67e805e265a4ba13dece692c4a94d09))

## 1.0.0 (2026-03-10)

### Features

* add Docker support (FrankenPHP) and Swagger UI support ([0047e96](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/0047e96af1c1f17822eef39555dcbda59333511c))
* initial implementation of PHP OpenAPI mock server with Mezzio, Codeception tests, and AI reporting ([6db4fa5](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/6db4fa503496c8c74595944d6b45e402ccee5f57))
* support authentication headers for remote OpenAPI specifications ([2e3e653](https://github.com/WebProject-xyz/php-openapi-mock-server/commit/2e3e653bc928a172b2385261ad335dcb6428226e))
