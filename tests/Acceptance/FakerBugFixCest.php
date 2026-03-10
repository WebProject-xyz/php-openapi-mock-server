<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Acceptance;

use WebProject\PhpOpenApiMockServer\Tests\Support\AcceptanceTester;

class FakerBugFixCest
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

    /**
     * Verify that requesting a user by ID always returns a positive ID matching the path variable,
     * and ensure that the response object is never empty across multiple iterations.
     */
    public function testUserByIdInjectionAndNonEmptyResponse(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/json');
        $I->haveHttpHeader('X-OpenApi-Mock-Active', 'true');

        // Run multiple times to catch probabilistic bugs (negative IDs or empty responses)
        for ($id = 1; $id <= 20; ++$id) {
            $I->sendGet('/users/' . $id);
            $I->seeResponseCodeIs(200);
            $I->seeResponseIsJson();

            $response = json_decode($I->grabResponse(), true);

            // 1. Ensure response is not empty
            $I->assertIsArray($response);
            $I->assertNotEmpty($response, "Response should not be empty for user ID: $id");

            // 2. Ensure 'id' key exists and matches path variable
            $I->assertArrayHasKey('id', $response, "Response should contain 'id' key for user ID: $id");
            $I->assertEquals($id, $response['id'], "Returned ID should match path variable: $id");

            // 3. Ensure ID is positive
            $I->assertGreaterThan(0, $response['id'], "ID should be positive for user ID: $id");

            // 4. Check other properties (optional in schema, so they might not always be there)
            if (isset($response['name'])) {
                $I->assertIsString($response['name']);
                $I->assertNotEmpty($response['name']);
            }
        }
    }
}
