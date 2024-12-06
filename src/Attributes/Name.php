<?php

namespace haddowg\JsonApiOpenApi\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Name implements StringValueAttribute
{
    public function __construct(
        private string $name,
    ) {}

    public function value(): string
    {
        return $this->name;
    }

    public function key(): string
    {
        return 'name';
    }
}
