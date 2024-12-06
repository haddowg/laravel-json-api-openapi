<?php

namespace haddowg\JsonApiOpenApi\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT | \Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
final readonly class Description implements StringValueAttribute
{
    public function __construct(
        private string $description,
        private bool $translate = true,
    ) {}

    public function key(): string
    {
        return 'description';
    }

    public function value(): string
    {
        return $this->translate ? trans($this->description) : $this->description;
    }
}
