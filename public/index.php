<?php
declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

require __DIR__ . '/../vendor/autoload.php';

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../config/container.php';

/** @var Application $app */
$app = $container->get(Application::class);

/** @var MiddlewareFactory $factory */
$factory = $container->get(MiddlewareFactory::class);

// Execute programmatic pipeline and routing
(require __DIR__ . '/../config/pipeline.php')($app, $factory);
(require __DIR__ . '/../config/routes.php')($app, $factory);

$app->run();
