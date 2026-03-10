<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\JsonAcceptance;

use WebProject\PhpOpenApiMockServer\Tests\Support\AcceptanceTester;

class JsonSpecCest
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

    public function testGetProductsFromJsonSpec(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGet('/products');
        $acceptanceTester->seeResponseCodeIs(200);
        $acceptanceTester->seeResponseIsJson();

        $response = json_decode($acceptanceTester->grabResponse(), true);
        $acceptanceTester->assertIsArray($response);
    }

    public function testSwaggerUIPointsToJson(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGet('/');
        $acceptanceTester->seeResponseCodeIs(200);
        $acceptanceTester->seeResponseContains("url: '/openapi.json'");
    }

    public function testOpenApiJsonRoute(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGet('/openapi.json');
        $acceptanceTester->seeResponseCodeIs(200);
        $acceptanceTester->seeHttpHeader('Content-Type', 'application/json');
        $acceptanceTester->seeResponseContains('"openapi": "3.0.0"');
    }
}
