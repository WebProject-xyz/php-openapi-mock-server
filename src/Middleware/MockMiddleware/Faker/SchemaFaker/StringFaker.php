<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use cebe\openapi\spec\Schema;
use Faker\Factory;
use Faker\Generator;
use function reset;
use function str_repeat;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;

/** @internal */
final readonly class StringFaker implements FakerInterface
{
    private Generator $generator;

    public function __construct()
    {
        $this->generator = Factory::create();
    }

    public function generate(Schema $schema, Options $options, FakerRegistry $fakerRegistry, FakerContext $fakerContext): ?string
    {
        if (MockStrategy::STATIC === $options->getStrategy()) {
            return $this->generateStatic($schema);
        }

        return $this->generateDynamic($schema);
    }

    private function generateDynamic(Schema $schema): string
    {
        if (!empty($schema->enum)) {
            /** @var string $value */
            $value = $this->generator->randomElement($schema->enum);

            return $value;
        }

        if (null !== $schema->pattern) {
            return $this->generator->regexify($schema->pattern);
        }

        $maxLength = $schema->maxLength ?? 255;

        return match ($schema->format) {
            'date'      => $this->generator->date(),
            'date-time' => $this->generator->iso8601(),
            'email'     => $this->generator->email(),
            'uuid'      => $this->generator->uuid(),
            'uri'       => $this->generator->url(),
            'hostname'  => $this->generator->domainName(),
            'ipv4'      => $this->generator->ipv4(),
            'ipv6'      => $this->generator->ipv6(),
            default     => $this->generator->text($maxLength > 5 ? $maxLength : 20),
        };
    }

    private function generateStatic(Schema $schema): ?string
    {
        if (null !== $schema->default) {
            return (string) $schema->default;
        }

        if (null !== $schema->example) {
            return (string) $schema->example;
        }

        if ($schema->nullable) {
            return null;
        }

        if (!empty($schema->enum)) {
            /** @var array<string> $enums */
            $enums = $schema->enum;

            return (string) reset($enums);
        }

        $minLength = $schema->minLength ?? 0;

        return match ($schema->format) {
            'date'      => '2023-01-01',
            'date-time' => '2023-01-01T00:00:00Z',
            'email'     => 'user@example.com',
            'uuid'      => '00000000-0000-0000-0000-000000000000',
            'uri'       => 'https://example.com',
            'hostname'  => 'example.com',
            'ipv4'      => '127.0.0.1',
            'ipv6'      => '::1',
            'binary',
            'byte'      => 'YmFzZTY0LWVudGl0eQ==',
            default     => $minLength > 0 ? str_repeat('a', $minLength) : 'string',
        };
    }
}
