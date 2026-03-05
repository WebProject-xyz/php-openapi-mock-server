<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\FailureAcceptance;

use WebProject\PhpOpenApiMockServer\Tests\Support\AcceptanceTester;

class LoadFailureCest
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

    public function testSpecLoadFailureReturnsProblemDetails(AcceptanceTester $I): void
    {
        // Hits the server on port 8083 (configured with non-existent spec)
        $I->sendGet('/');
        $I->seeResponseCodeIs(500);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'title' => 'Failed to load OpenAPI specification',
            'type'  => 'CONFIG_ERROR',
        ]);
    }
}
