<?php

namespace haddowg\JsonApiOpenApi\Events;

use haddowg\JsonApiOpenApi\Contracts\OpenApiBuilderContract;

class Documenting
{
    public function __construct(public OpenApiBuilderContract $openApiBuilder) {}
}
