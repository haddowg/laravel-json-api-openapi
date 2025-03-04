<?php

namespace haddowg\JsonApiOpenApi\Tests;

use haddowg\JsonApiOpenApi\JsonApiOpenApiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function getEnvironmentSetUp($app) {}

    protected function getPackageProviders($app)
    {
        return [
            JsonApiOpenApiServiceProvider::class,
        ];
    }
}
