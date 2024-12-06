<?php

namespace haddowg\JsonApiOpenApi\Attributes;

use cebe\openapi\spec\Schema;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class Filter
{
    public function __construct(
        private string $key,
        private Schema $schema,
        private ?string $name = null,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function schema(): Schema
    {
        return $this->schema;
    }
}
