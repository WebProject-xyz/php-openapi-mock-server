<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Acceptance;

use function is_array;
use WebProject\PhpOpenApiMockServer\Tests\Support\AcceptanceTester;

class MockServerCest
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

    public function testGetUsers(AcceptanceTester $I): void
    {
        $I->sendGet('/users');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        // The middleware generates random data, sometimes empty arrays if it's optional
        // or just weirdly structured.
        $I->assertTrue(is_array(json_decode($I->grabResponse(), true)));
    }

    public function testGetUserById(AcceptanceTester $I): void
    {
        $I->sendGet('/users/1');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $response = json_decode($I->grabResponse(), true);
        // It should be an object (array in PHP)
        $I->assertIsArray($response);
    }

    public function testNotFoundInSpecButExistsInMezzio(AcceptanceTester $I): void
    {
        // We forced X-OpenApi-Mock-Active to true, so the middleware will intercept this
        // and return its own 404 because it's not in the spec.
        $I->sendGet('/');
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson(['type' => 'NO_PATH_MATCHED_ERROR']);
    }

    public function testDisableMockViaHeader(AcceptanceTester $I): void
    {
        // If we explicitly set it to false, it should fall through to Mezzio's root handler
        $I->haveHttpHeader('X-OpenApi-Mock-Active', 'false');
        $I->sendGet('/');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['message' => 'OpenAPI Mock Server is running!']);
    }
}
