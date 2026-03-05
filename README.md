# PHP OpenAPI Mock Server

[![CI](https://github.com/WebProject-xyz/php-openapi-mock-server/actions/workflows/ci.yml/badge.svg)](https://github.com/WebProject-xyz/php-openapi-mock-server/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

> **A lightweight, zero-docker OpenAPI mock server built with Mezzio.**

This project provides a standalone PHP application that serves mock data based on an OpenAPI 3.x specification. It's designed for rapid prototyping, frontend development, and CI environments where you want to avoid the overhead of Docker.

---

## Features

- **PSR-15 Compliant:** Built on the robust [Mezzio](https://docs.mezzio.dev/) middleware stack.
- **Zero-Docker:** Runs on the standard PHP built-in web server.
- **Dynamic Spec Loading:** Point to any OpenAPI spec file (local or URL) via environment variables.
- **Automatic Data Generation:** Uses [php-openapi-faker](https://github.com/canvural/php-openapi-faker) to generate realistic data when examples are missing.
- **Problem Details:** Structured error responses using the [RFC 7807](https://tools.ietf.org/html/rfc7807) (Problem Details for HTTP APIs) standard.
- **CI Ready:** Includes Codeception acceptance tests and GitHub Actions workflow.

---

## Installation

```bash
git clone https://github.com/WebProject-xyz/php-openapi-mock-server.git
cd php-openapi-mock-server
composer install
```

Requires PHP `^8.3`.

---

## Usage

### Start the Server

Run the PHP built-in server from the project root:

```bash
php -S localhost:8080 -t public
```

The server will now be available at `http://localhost:8080`.

### Use a Custom Spec

You can specify a different OpenAPI file (local path or URL) using the `OPENAPI_SPEC` environment variable. Both **YAML** and **JSON** formats are supported:

```bash
# Using a JSON spec
OPENAPI_SPEC=data/openapi.json php -S localhost:8080 -t public

# Using a remote YAML spec
OPENAPI_SPEC=https://raw.githubusercontent.com/OAI/OpenAPI-Specification/main/examples/v3.0/petstore.yaml php -S localhost:8080 -t public
```

### Enable/Disable Mocking

By default, the server is forced into "mock mode" for all requests. You can explicitly control this using the `X-OpenApi-Mock-Active` header:

- `X-OpenApi-Mock-Active: true` (default)
- `X-OpenApi-Mock-Active: false` (falls through to Mezzio handlers)

---

## Testing

The project uses [Codeception](https://codeception.com) for acceptance testing. The server is automatically started and stopped during the test run.

```bash
composer test:build   # rebuild Codeception actor classes
composer test         # run tests
composer stan         # PHPStan static analysis
composer cs:check     # check code style
composer cs:fix       # auto-fix code style
```

---

## Project Structure

- `public/index.php`: The entry point and middleware pipeline configuration.
- `data/openapi.yaml`: The default OpenAPI specification file.
- `tests/`: Codeception acceptance tests.
- `.github/workflows/ci.yml`: GitHub Actions CI configuration.

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

## License

MIT
