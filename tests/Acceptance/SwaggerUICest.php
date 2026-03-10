<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Acceptance;

use WebProject\PhpOpenApiMockServer\Tests\Support\AcceptanceTester;

class SwaggerUICest
{
    public function _before(AcceptanceTester $acceptanceTester): void
    {
        // Give the built-in server some time to start on the first test
        static $started = false;
        if (!$started) {
            usleep(1000000); // 1.0 second
            $started = true;
        }
    }

    public function testSwaggerUIHome(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGet('/');
        $acceptanceTester->seeResponseCodeIs(200);
        $acceptanceTester->seeResponseContains('<title>OpenAPI Mock Server - Swagger UI</title>');
        $acceptanceTester->seeResponseContains('swagger-ui-bundle.js');
        $acceptanceTester->seeResponseContains('SwaggerUIStandalonePreset');
    }

    public function testSwaggerUISpecUrl(AcceptanceTester $acceptanceTester): void
    {
        // Default is yaml
        $acceptanceTester->sendGet('/');
        $acceptanceTester->seeResponseContains("url: '/openapi.yaml'");
    }

    public function testOpenApiYamlRoute(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGet('/openapi.yaml');
        $acceptanceTester->seeResponseCodeIs(200);
        // Should contain some YAML-like content (default spec)
        $acceptanceTester->seeResponseContains('openapi: 3.0.0');
        $acceptanceTester->seeHttpHeader('Content-Type', 'text/yaml;charset=UTF-8');
    }
}
