<?php

namespace haddowg\JsonApiOpenApi\Events;

use cebe\openapi\spec\OpenApi;
use haddowg\JsonApiOpenApi\Contracts\OpenApiBuilderContract;

class Documented
{
    public function __construct(public OpenApiBuilderContract $openApiBuilder, public OpenApi $openApi) {}
}
