<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\SchemaFaker;

use cebe\openapi\spec\Schema;
use Faker\Factory;
use Faker\Provider\Base;
use Faker\Provider\Lorem;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\MockStrategy;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Options;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Utils\RegexUtils;
use WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Utils\StringUtils;

use function base64_encode;
use function max;
use function reset;

/** @internal */
final class StringFaker implements FakerInterface
{
    public function generate(Schema $schema, Options $options, FakerRegistry $fakerRegistry): string
    {
        if ($options->getStrategy() === MockStrategy::STATIC) {
            return $this->generateStatic($schema);
        }

        return $this->generateDynamic($schema);
    }

    private function generateDynamic(Schema $schema): string
    {
        if (! empty($schema->enum)) {
            return (string) Base::randomElement($schema->enum);
        }

        if (! empty($schema->format)) {
            return $this->generateDynamicFromFormat($schema);
        }

        if (! empty($schema->pattern)) {
            return Lorem::regexify($schema->pattern);
        }

        $minLength = $schema->minLength ?? 0;
        $maxLength = $schema->maxLength ?? max(140, $minLength + 1);

        return StringUtils::ensureLength(Lorem::sentence(), $minLength, $maxLength);
    }

    private function generateDynamicFromFormat(Schema $schema): string
    {
        $generator = Factory::create();

        return match ($schema->format) {
            'date'           => $generator->date(),
            'date-time'      => $generator->iso8601(),
            'email'          => $generator->email(),
            'uuid'           => $generator->uuid(),
            'ipv4'           => $generator->ipv4(),
            'ipv6'           => $generator->ipv6(),
            'hostname'       => $generator->domainName(),
            'binary'         => StringUtils::convertToBinary(Lorem::sentence()),
            'byte'           => base64_encode(Lorem::sentence()),
            'password'       => Lorem::sentence(),
            default          => Lorem::sentence(),
        };
    }

    private function generateStatic(Schema $schema): string
    {
        if ($schema->example !== null) {
            return (string) $schema->example;
        }

        if ($schema->default !== null) {
            return (string) $schema->default;
        }

        if (! empty($schema->enum)) {
            $enums = $schema->enum;

            return (string) reset($enums);
        }

        if (! empty($schema->format)) {
            return $this->generateStaticFromFormat($schema);
        }

        if (! empty($schema->pattern)) {
            return RegexUtils::generateSample($schema->pattern);
        }

        $minLength = $schema->minLength ?? 0;
        $maxLength = $schema->maxLength ?? max(140, $minLength + 1);

        return StringUtils::ensureLength('string', $minLength, $maxLength);
    }

    private function generateStaticFromFormat(Schema $schema): string
    {
        $minLength = $schema->minLength;
        $maxLength = $schema->maxLength;

        return match ($schema->format) {
            'date'           => '2023-01-01',
            'date-time'      => '2023-01-01T00:00:00Z',
            'email'          => 'user@example.com',
            'uuid'           => '00000000-0000-0000-0000-000000000000',
            'ipv4'           => '127.0.0.1',
            'ipv6'           => '::1',
            'hostname'       => 'example.com',
            'binary'         => StringUtils::convertToBinary('string'),
            'byte'           => base64_encode('string'),
            'password'       => StringUtils::ensureLength('pa$$wordqwerty!@#$%^123456', $minLength, $maxLength),
            default          => StringUtils::ensureLength('string', $minLength, $maxLength),
        };
    }
}
