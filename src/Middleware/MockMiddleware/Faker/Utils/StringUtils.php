<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Utils;

use function base_convert;
use function ceil;
use function implode;
use function max;
use function str_repeat;
use function str_split;
use function strlen;
use function substr;
use function unpack;
use Webmozart\Assert\Assert;

/** @internal */
final class StringUtils
{
    public static function convertToBinary(string $text): string
    {
        $characters = str_split($text);

        $binary = [];
        foreach ($characters as $character) {
            $data = unpack('H*', $character);
            Assert::isArray($data);
            Assert::keyExists($data, 1);
            $binary[] = base_convert((string) $data[1], 16, 2);
        }

        return implode(' ', $binary);
    }

    public static function ensureLength(string $text, ?int $minLength = null, ?int $maxLength = null): string
    {
        if (null === $minLength) {
            $minLength = 0;
        }

        if (null === $maxLength) {
            $maxLength = strlen($text);
        }

        if (0 === max($minLength, $maxLength)) {
            return $text;
        }

        if ($maxLength <= $minLength) {
            $maxLength = $minLength;
        }

        if (strlen($text) <= $minLength) {
            $text = str_repeat($text, (int) ceil($minLength / strlen($text)));
        }

        if (strlen($text) > $maxLength) {
            return substr($text, 0, $maxLength);
        }

        return $text;
    }
}
