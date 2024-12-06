<?php

namespace haddowg\JsonApiOpenApi\Attributes;

interface StringValueAttribute
{
    public function key(): string;

    public function value(): string;
}
