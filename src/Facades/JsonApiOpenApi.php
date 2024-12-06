<?php

namespace haddowg\JsonApiOpenApi\Facades;

use Illuminate\Support\Facades\Facade;

class JsonApiOpenApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \haddowg\JsonApiOpenApi\JsonApiOpenApi::class;
    }
}
