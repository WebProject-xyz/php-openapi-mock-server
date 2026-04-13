<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

final class NotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
