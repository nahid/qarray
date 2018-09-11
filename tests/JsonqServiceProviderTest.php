<?php

namespace Nahid\JsonQ\Tests;

use GrahamCampbell\TestBenchCore\ServiceProviderTrait;
use Nahid\JsonQ\Query;

/**
 * This is the service provider test class.
 */
class JsonqServiceProviderTest extends TestCase
{
    use ServiceProviderTrait;

    public function testJsonqIsInjectable()
    {
        $this->assertIsInjectable(Query::class);
    }
}
