<?php
declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\FailureAcceptance;

use WebProject\PhpOpenApiMockServer\Tests\Support\AcceptanceTester;

class LoadFailureCest
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

    public function testSpecLoadFailureReturnsProblemDetails(AcceptanceTester $acceptanceTester): void
    {
        // Hits the server on port 8083 (configured with non-existent spec)
        $acceptanceTester->sendGet('/');
        $acceptanceTester->seeResponseCodeIs(500);
        $acceptanceTester->seeResponseIsJson();
        $acceptanceTester->seeResponseContainsJson([
            'title' => 'Failed to load OpenAPI specification',
            'type'  => 'CONFIG_ERROR',
        ]);
    }
}
