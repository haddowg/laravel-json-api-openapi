<?php

namespace haddowg\JsonApiOpenApi\Attributes;

use cebe\openapi\spec\Schema as SpecSchema;
use Illuminate\Support\Arr;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Schema implements SpecAttribute
{
    private SpecSchema $schema;

    // @phpstan-ignore-next-line
    public function __construct(private array $data)
    {
        $this->schema = new SpecSchema($data);
        if (!$this->schema->validate()) {
            throw new \InvalidArgumentException(Arr::first($this->schema->getErrors()));
        }
    }

    public function toSpec(): SpecSchema
    {
        return new SpecSchema($this->data);
    }
}
