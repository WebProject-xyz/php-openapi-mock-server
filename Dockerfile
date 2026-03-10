# Stage 1: Install dependencies
FROM composer:2 AS composer_build

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
FROM dunglas/frankenphp:latest

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
