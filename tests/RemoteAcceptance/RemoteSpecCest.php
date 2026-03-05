<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\RemoteAcceptance;

use WebProject\PhpOpenApiMockServer\Tests\Support\AcceptanceTester;

class RemoteSpecCest
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

    public function testGetByUsernameFromRemoteSpec(AcceptanceTester $I): void
    {
        // Redocly openapi-template has /users/{username} under /api/v1
        $I->sendGet('/api/v1/users/admin');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $response = json_decode($I->grabResponse(), true);
        $I->assertIsArray($response);
    }
}
