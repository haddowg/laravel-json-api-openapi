<?php

namespace haddowg\JsonApiOpenApi\Factories;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use haddowg\JsonApiOpenApi\Descriptors\Enum;

class SchemaFactory
{
    /**
     * @param class-string<\BackedEnum> $enum
     */
    public static function enum(string $enum, ?string $name = null): Schema|Reference
    {
        return Enum::make($enum)->name($name)->build();
    }
}
