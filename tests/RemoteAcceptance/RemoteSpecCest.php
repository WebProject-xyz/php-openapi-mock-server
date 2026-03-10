<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\RemoteAcceptance;

use WebProject\PhpOpenApiMockServer\Tests\Support\AcceptanceTester;

class RemoteSpecCest
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

    public function testGetByUsernameFromRemoteSpec(AcceptanceTester $acceptanceTester): void
    {
        // Redocly openapi-template has /users/{username} under /api/v1
        $acceptanceTester->sendGet('/api/v1/users/admin');
        $acceptanceTester->seeResponseCodeIs(200);
        $acceptanceTester->seeResponseIsJson();

        $response = json_decode($acceptanceTester->grabResponse(), true);
        $acceptanceTester->assertIsArray($response);
    }
}
