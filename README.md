# PHP OpenAPI Mock Server

[![CI](https://github.com/WebProject-xyz/php-openapi-mock-server/actions/workflows/ci.yml/badge.svg)](https://github.com/WebProject-xyz/php-openapi-mock-server/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg)](https://phpstan.org/)
[![Coverage](https://img.shields.io/badge/coverage-82%25-brightgreen.svg)](tests/_output/coverage.txt)

> **A high-performance, lightweight, zero-docker OpenAPI 3.x mock server built with Mezzio.**

This project provides a standalone PHP application that serves dynamic mock data based on your OpenAPI specification. It is optimized for speed, low memory footprint, and seamless CI integration.

---

## 🚀 Features

- **Blazing Fast:** Sub-millisecond initialization and ~4ms request processing.
- **Interactive Documentation:** Integrated **Swagger UI** at the root (`/`) for real-time spec exploration.
- **Stateless Service Architecture:** Built with a high-performance `FakerRegistry` for efficient service management.
- **Type-Safe Enums:** Robust implementation using PHP 8.3 Enums for `MockStrategy`, `HttpMethod`, `FakerType`, and `FakerContext`.
- **PSR-15 Compliant:** Built on the industrial-strength [Mezzio](https://docs.mezzio.dev/) middleware stack.
- **Docker Ready:** Production-ready [FrankenPHP](https://frankenphp.dev/) image with **Hot-Reload** support for development.
- **Intelligent Faking:** Automatically generates realistic mock data using spec `examples`, `defaults`, and schema constraints (`anyOf`, `oneOf`, `allOf`).
- **OpenAPI 3.1 Support:** Advanced support for numeric exclusive constraints and modern spec features.
- **Smart Caching:** Built-in **PSR-6 caching** and **schema memoization** to eliminate redundant parsing and resolution.
- **Validation:** Optional request and response validation to ensure your clients and mocks stay in sync.
- **Problem Details:** Native support for [RFC 7807](https://tools.ietf.org/html/rfc7807) error responses with detailed diagnostics.

---

## 📦 Installation

```bash
git clone https://github.com/WebProject-xyz/php-openapi-mock-server.git
cd php-openapi-mock-server
composer install
```

*Requires PHP `^8.3` with `opcache` enabled for best performance.*

---

## 🖥️ Usage

### Start the Server
```bash
php -S localhost:8080 -t public
```

### Docker
You can also run the mock server using Docker or Docker Compose (powered by [FrankenPHP](https://frankenphp.dev/)).

#### Using Docker Compose (with Hot-Reload)
The `compose.yml` is configured to mount your local code, allowing for real-time updates during development.
```bash
# Start with default spec (data/openapi.yaml)
docker compose up -d

# Start with a specific local spec
OPENAPI_SPEC=data/my-spec.yaml docker compose up -d

# Start with a remote URL
OPENAPI_SPEC=https://example.com/openapi.yaml docker compose up -d
```

#### Using Docker
```bash
docker build -t openapi-mock-server .
docker run -d -p 8080:80 -e OPENAPI_SPEC=data/openapi.yaml openapi-mock-server
```

### Advanced Configuration
You can control the server via environment variables or headers:

| Variable / Header | Description | Default |
| :--- | :--- | :--- |
| `OPENAPI_SPEC` | Path or URL to your `.yaml` or `.json` spec. | `data/openapi.yaml` |
| `X-OpenApi-Mock-Active` | Toggle mock server activation (`true`/`false`). | `true` |
| `X-OpenApi-Mock-StatusCode` | Force a specific response status code. | *Automatic Fallback* |
| `X-OpenApi-Mock-Example` | Force a specific named example from the spec. | `default` |

---

## 📊 Performance

We take performance seriously. Using **PHPBench**, we track the overhead of our middleware and faking logic.

### Benchmark Results
*Running on PHP 8.3 with OPcache & JIT enabled.*

| Operation | Average Time | Memory Usage |
| :--- | :--- | :--- |
| **Middleware Creation** | **~21.0 μs** | ~6.0 MB |
| **Full Mock Request** | **~4.20 ms** | ~6.0 MB |

> *Note: Metrics include full Request Validation + Schema Resolution + Data Generation + Response Validation.*

To run benchmarks locally:
```bash
composer bench
```

---

## 🛠️ Development & Testing

We maintain high code quality standards:
- **Unit Tests**: Full coverage of fakers, validators, and factories.
- **Acceptance Tests**: End-to-end verification using the built-in PHP server.
- **Static Analysis**: PHPStan Level 8 (Strict).
- **Refactoring**: Rector PHP 8.3 ruleset.

### Key Commands
```bash
composer test         # Run all tests (Acceptance + Unit)
composer test:coverage # Generate coverage report (82%+)
composer stan         # Run static analysis
composer rector       # Run refactoring dry-run
composer cs:fix       # Standardize code style
```
---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'feat: add some amazing feature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📜 License

Distributed under the **MIT** License. See `LICENSE` for more information.
