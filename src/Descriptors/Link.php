<?php

namespace haddowg\JsonApiOpenApi\Descriptors;

use cebe\openapi\spec\Schema;
use haddowg\JsonApiOpenApi\Concerns\BuilderAware;
use haddowg\JsonApiOpenApi\Enums\DefaultSchema;
use LaravelJsonApi\Core\Document\Link as JsonApiLink;

readonly class Link
{
    use BuilderAware;

    public static function make(JsonApiLink $link): Schema
    {
        if ($link->hasMeta()) {
            return new Schema(['type' => 'object',
                'schema' => static::builder()->components()->getSchema(DefaultSchema::LinkObject),
                'example' => $link->href()->toString(),
            ]);
        }

        return new Schema([
            'type' => 'string',
            'format' => 'url',
            'example' => $link->href()->toString(),
        ]);
    }

    public static function makeSelf(string $url): Schema
    {
        return static::make(new JsonApiLink('self', $url));
    }
}
