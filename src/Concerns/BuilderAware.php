<?php

namespace haddowg\JsonApiOpenApi\Concerns;

use haddowg\JsonApiOpenApi\Contracts\OpenApiBuilderContract;
use haddowg\JsonApiOpenApi\Facades\JsonApiOpenApi;

trait BuilderAware
{
    public static function builder(): OpenApiBuilderContract
    {
        return JsonApiOpenApi::builder();
    }
}
