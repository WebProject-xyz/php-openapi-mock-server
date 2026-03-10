<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Acceptance;

use function is_array;
use WebProject\PhpOpenApiMockServer\Tests\Support\AcceptanceTester;

class MockServerCest
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

    public function testGetUsers(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGet('/users');
        $acceptanceTester->seeResponseCodeIs(200);
        $acceptanceTester->seeResponseIsJson();
        // The middleware generates random data, sometimes empty arrays if it's optional
        // or just weirdly structured.
        $acceptanceTester->assertTrue(is_array(json_decode($acceptanceTester->grabResponse(), true)));
    }

    public function testGetUserById(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendGet('/users/1');
        $acceptanceTester->seeResponseCodeIs(200);
        $acceptanceTester->seeResponseIsJson();

        $response = json_decode($acceptanceTester->grabResponse(), true);
        // It should be an object (array in PHP)
        $acceptanceTester->assertIsArray($response);
    }

    public function testNotFoundInSpecButExistsInMezzio(AcceptanceTester $acceptanceTester): void
    {
        // Root path is now allowed to bypass mock processing to show Swagger UI or status JSON
        $acceptanceTester->haveHttpHeader('Accept', 'application/json');
        $acceptanceTester->sendGet('/');
        $acceptanceTester->seeResponseCodeIs(200);
        $acceptanceTester->seeResponseContainsJson(['message' => 'OpenAPI Mock Server is running!']);
    }

    public function testPostReturns202Accepted(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->haveHttpHeader('Content-Type', 'application/json');
        $acceptanceTester->sendPost('/users/1/tasks', ['title' => 'My Task']);
        $acceptanceTester->seeResponseCodeIs(202);
        $acceptanceTester->seeResponseIsJson();

        $response = json_decode($acceptanceTester->grabResponse(), true);
        $acceptanceTester->assertIsArray($response);
    }

    public function testDeleteReturns204NoContent(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->sendDelete('/users/1/tasks');
        $acceptanceTester->seeResponseCodeIs(204);
    }

    public function testDisableMockViaHeader(AcceptanceTester $acceptanceTester): void
    {
        // If we explicitly set it to false, it should fall through to Mezzio's root handler
        $acceptanceTester->haveHttpHeader('X-OpenApi-Mock-Active', 'false');
        $acceptanceTester->haveHttpHeader('Accept', 'application/json');
        $acceptanceTester->sendGet('/');
        $acceptanceTester->seeResponseCodeIs(200);
        $acceptanceTester->seeResponseContainsJson(['message' => 'OpenAPI Mock Server is running!']);
    }
}
