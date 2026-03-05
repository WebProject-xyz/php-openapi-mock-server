<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\JsonAcceptance;

use WebProject\PhpOpenApiMockServer\Tests\Support\AcceptanceTester;

class JsonSpecCest
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

    public function testGetProductsFromJsonSpec(AcceptanceTester $I): void
    {
        $I->sendGet('/products');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $response = json_decode($I->grabResponse(), true);
        $I->assertIsArray($response);
    }
}
