<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Middleware\MockMiddleware\Faker\Utils;

use Webmozart\Assert\Assert;

use function base_convert;
use function ceil;
use function implode;
use function max;
use function str_repeat;
use function str_split;
use function strlen;
use function substr;
use function unpack;

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

    public static function ensureLength(string $text, int|null $minLength = null, int|null $maxLength = null): string
    {
        if ($minLength === null) {
            $minLength = 0;
        }

        if ($maxLength === null) {
            $maxLength = strlen($text);
        }

        if (max($minLength, $maxLength) === 0) {
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
