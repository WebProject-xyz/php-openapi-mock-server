<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Acceptance;

use WebProject\PhpOpenApiMockServer\Tests\Support\AcceptanceTester;

class FakerBugFixCest
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

    /**
     * Verify that requesting a user by ID always returns a positive ID matching the path variable,
     * and ensure that the response object is never empty across multiple iterations.
     */
    public function testUserByIdInjectionAndNonEmptyResponse(AcceptanceTester $acceptanceTester): void
    {
        $acceptanceTester->haveHttpHeader('Accept', 'application/json');
        $acceptanceTester->haveHttpHeader('X-OpenApi-Mock-Active', 'true');

        // Run multiple times to catch probabilistic bugs (negative IDs or empty responses)
        for ($id = 1; $id <= 20; ++$id) {
            $acceptanceTester->sendGet('/users/' . $id);
            $acceptanceTester->seeResponseCodeIs(200);
            $acceptanceTester->seeResponseIsJson();

            $response = json_decode($acceptanceTester->grabResponse(), true);

            // 1. Ensure response is not empty
            $acceptanceTester->assertIsArray($response);
            $acceptanceTester->assertNotEmpty($response, 'Response should not be empty for user ID: ' . $id);

            // 2. Ensure 'id' key exists and matches path variable
            $acceptanceTester->assertArrayHasKey('id', $response, 'Response should contain \'id\' key for user ID: ' . $id);
            $acceptanceTester->assertEquals($id, $response['id'], 'Returned ID should match path variable: ' . $id);

            // 3. Ensure ID is positive
            $acceptanceTester->assertGreaterThan(0, $response['id'], 'ID should be positive for user ID: ' . $id);

            // 4. Check other properties (optional in schema, so they might not always be there)
            if (isset($response['name'])) {
                $acceptanceTester->assertIsString($response['name']);
                $acceptanceTester->assertNotEmpty($response['name']);
            }
        }
    }
}
