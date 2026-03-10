<?php

declare(strict_types=1);

namespace WebProject\PhpOpenApiMockServer\Tests\Support;

use Codeception\Actor;
use WebProject\PhpOpenApiMockServer\Tests\Support\_generated\AcceptanceTesterActions;

/**
 * Inherited Methods.
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends Actor
{
    use AcceptanceTesterActions;

    /**
     * Define custom actions here.
     */
}
