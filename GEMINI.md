# Gemini CLI Instructional Context: PHP OpenAPI Mock Server

This document provides foundational context and instructions for AI agents interacting with the `php-openapi-mock-server` codebase.

## Project Overview
**PHP OpenAPI Mock Server** is a high-performance, lightweight, zero-Docker mock server built with the Mezzio framework. It serves mock data based on OpenAPI 3.x specifications (JSON or YAML) and is designed for rapid prototyping and CI environments.

- **Type:** PHP Web Application (PSR-15 Middleware Stack)
- **Framework:** [Mezzio](https://docs.mezzio.dev/)
- **Core Libraries:** 
  - `league/openapi-psr7-validator`: For request and response validation against OpenAPI specs.
  - `devizzent/cebe-php-openapi`: For parsing OpenAPI specifications.
  - `laminas/laminas-diactoros`: PSR-7 implementation.
  - `webmozart/assert`: For strict runtime type verification.
- **Language:** PHP 8.3+ (Strict Types enabled)

## Architecture & Logic
The application operates primarily through a series of PSR-15 middlewares and a stateless service registry:
1. **`ForceMockActiveMiddleware`**: Ensures the mock server is active by default by setting the `X-OpenApi-Mock-Active` header.
2. **`OpenApiMockMiddleware`**: The core logic that intercepts requests, validates them, and returns faked responses. It handles all documentation routes (`/`, `/openapi.yaml`) by passing them to the standard routing stack.
3. **`FakerRegistry`**: A central, stateless registry for all mock generation services.
4. **Type-Safe Enums**: Extensive use of PHP 8.3 Enums for `FakerType`, `FakerContext`, `HttpMethod`, and `RequestErrorType` to eliminate magic strings and harden type safety.
5. **Factories:** Found in `src/Factory/` and use **PSR-17 interfaces** (`ResponseFactoryInterface`, `StreamFactoryInterface`) for dependency injection.

## Building and Running

### Development Server
Run the built-in PHP server:
```bash
php -S localhost:8080 -t public
```

### Docker (FrankenPHP)
Optimized [FrankenPHP](https://frankenphp.dev/) environment with **Hot-Reload** support:
```bash
docker compose up -d
```

### Environment Variables
- `OPENAPI_SPEC`: Path or URL to the OpenAPI specification file (Default: `data/openapi.yaml`).

### Key Composer Scripts
- `composer test`: Runs the full Codeception test suite (**95 tests passing**).
- `composer test:coverage`: Generates code coverage reports (**~82% coverage**).
- `composer bench`: Runs the PHPBench performance suite.
- `composer stan`: Runs PHPStan static analysis (**Level 8 clean**).
- `composer rector:fix`: Applies automated refactorings via Rector.
- `composer cs:fix`: Fixes coding standards via PHP-CS-Fixer.

## Development Conventions

### Coding Style
- **Standard:** PSR-12 / PER standard via PHP-CS-Fixer.
- **Strict Typing:** All PHP files MUST start with `declare(strict_types=1);`.
- **Modern PHP:** Leverage PHP 8.3 features like Enums, readonly properties, and constructor property promotion.

### PHPStan & Type Safety
- **Stricteness:** Maintain a zero-error baseline at **PHPStan Level 8**.
- **Assertions:** Use `Webmozart\Assert\Assert` for runtime type narrowing. This is foundational for the project's type safety.

### Testing Practices
- **Framework:** [Codeception](https://codeception.com/).
- **Suites:** `Acceptance`, `Unit`, `JsonAcceptance`, `RemoteAcceptance`.
- **Base Class:** Unit tests MUST extend `\Codeception\Test\Unit`.
- **Reproducing Bugs:** ALWAYS add a new regression test (unit or acceptance) before fixing a bug.

## Performance & Benchmarking
- **PSR-6 Caching:** Caches parsed OpenAPI specifications.
- **Schema Memoization:** Static caching in `SchemaFaker` avoids redundant recursive resolution.
- **Metrics:** Middleware creation is **~21μs**, and full mock request processing averages **~4.2ms**.

## Mocking Logic
- **`FakerRegistry`**: Manages stateless instances of `StringFaker`, `NumberFaker`, `ArrayFaker`, `ObjectFaker`, etc.
- **OpenAPI 3.1 Support:** Full support for numeric `exclusiveMinimum` and `exclusiveMaximum`.
- **Composition:** Robust handling of `anyOf`, `oneOf`, and `allOf`.
- **Swagger UI:** Automatically available at the root (`/`) path.
