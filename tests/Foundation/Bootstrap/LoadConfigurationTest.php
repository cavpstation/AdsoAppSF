<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use PHPUnit\Framework\TestCase;

class LoadConfigurationTest extends TestCase
{
    public function testLoadsBaseConfiguration()
    {
        $app = new Application();

        (new LoadConfiguration())->bootstrap($app);

        $this->assertSame('Laravel', $app['config']['app.name']);
    }

    public function testDontLoadBaseConfiguration()
    {
        $app = new Application();
        $app->dontMergeBaseConfiguration();

        (new LoadConfiguration())->bootstrap($app);

        $this->assertNull($app['config']['app.name']);
    }
}
