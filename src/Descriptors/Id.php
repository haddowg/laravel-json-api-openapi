<?php

namespace haddowg\JsonApiOpenApi\Descriptors;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use haddowg\JsonApiOpenApi\Contracts\OpenApiBuilderContract as OpenApiBuilder;
use haddowg\JsonApiOpenApi\Enums\DefaultSchema;
use LaravelJsonApi\Contracts\Schema\ID as JsonApiId;
use Ramsey\Uuid\Uuid;

readonly class Id
{
    public static function make(OpenApiBuilder $openApiBuilder, JsonApiId $id, bool $asRef = true): Schema|Reference
    {
        $format = null;
        $pattern = null;
        $name = class_basename($id);

        if ($name === 'ID') {
            $name = 'Id';
        }

        if ($id->match(Uuid::uuid7()->toString())) {
            $name = 'Uuid';
            $format = 'uuid';
        }

        if ($id->pattern() === '[0-7][0-9a-hjkmnp-tv-zA-HJKMNP-TV-Z]{25}') {
            $name = 'Ulid';
            $format = 'ulid';
        }

        if ($id->pattern() !== '[0-9]+' && $format === null) {
            $pattern = $id->pattern();
        }

        if ($format !== null || $pattern !== null) {
            if ($openApiBuilder->components()->hasSchema($name)) {
                return $openApiBuilder->components()->getSchema($name, $asRef);
            }

            $schema = new Schema(array_filter([
                'type' => 'string',
                'format' => $format,
                'pattern' => $pattern,
            ]));

            return $asRef ? $openApiBuilder->components()->addSchema($name, $schema) : $schema;
        }

        return $openApiBuilder->components()->getSchema(DefaultSchema::Id, $asRef);
    }
}
