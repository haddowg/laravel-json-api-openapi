<?php

namespace haddowg\JsonApiOpenApi\Attributes\Actions;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
class FetchMany implements ResourceAction
{
    public function __construct(
        public ?string $resourceType,
    ) {}
}
