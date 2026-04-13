# Stage 1: Install dependencies
FROM composer:2@sha256:698d3801b2a622ace460c4743c781282fcbcb733a4cbf8b31c44731e846585e8 AS composer_build

WORKDIR /app

# Copy only composer files first to leverage Docker cache
COPY composer.json ./

# Install production dependencies
# We ignore platform reqs here because we know they are met in the final image
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --ignore-platform-reqs

# Stage 2: Final image
FROM dunglas/frankenphp:latest@sha256:ba6690678662e4e2591d49f9128fb585fe524f0533a6d76feda5400e45e977bb

# Disable HTTPS by default for the container
ENV SERVER_NAME=:80

# Enable production PHP settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Install additional PHP extensions
RUN install-php-extensions \
    bcmath \
    curl \
    intl \
    zip \
    opcache \
    sodium

# Set the working directory
WORKDIR /app

# Copy vendor from builder
COPY --from=composer_build /app/vendor /app/vendor

# Copy application code
COPY . /app

# Set default environment variables
ENV OPENAPI_SPEC=/app/data/openapi.yaml
