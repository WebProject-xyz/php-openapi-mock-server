<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Acceptance;

use WebProject\PhpOpenApiMockServer\Tests\Support\AcceptanceTester;

class SwaggerUICest
{
    public function _before(AcceptanceTester $I): void
    {
        // Give the built-in server some time to start on the first test
        static $started = false;
        if (!$started) {
            usleep(1000000); // 1.0 second
            $started = true;
        }
    }

    public function testSwaggerUIHome(AcceptanceTester $I): void
    {
        $I->sendGet('/');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('<title>OpenAPI Mock Server - Swagger UI</title>');
        $I->seeResponseContains('swagger-ui-bundle.js');
        $I->seeResponseContains('SwaggerUIStandalonePreset');
    }

    public function testSwaggerUISpecUrl(AcceptanceTester $I): void
    {
        // Default is yaml
        $I->sendGet('/');
        $I->seeResponseContains("url: '/openapi.yaml'");
    }

    public function testOpenApiYamlRoute(AcceptanceTester $I): void
    {
        $I->sendGet('/openapi.yaml');
        $I->seeResponseCodeIs(200);
        // Should contain some YAML-like content (default spec)
        $I->seeResponseContains('openapi: 3.0.0');
        $I->seeHttpHeader('Content-Type', 'text/yaml;charset=UTF-8');
    }
}
