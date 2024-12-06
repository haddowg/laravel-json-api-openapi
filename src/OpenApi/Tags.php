<?php

namespace haddowg\JsonApiOpenApi\OpenApi;

use cebe\openapi\spec\Tag;
use haddowg\JsonApiOpenApi\Concerns\PerformsTranslation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

readonly class Tags
{
    use PerformsTranslation;

    /**
     * @var Collection<string, Tag>
     */
    private Collection $tags;

    public function __construct()
    {
        $this->tags = new Collection();
    }

    public static function make(): self
    {
        return new self();
    }

    public function addResourceType(string $resourceType): string
    {
        $name = $this->getTranslationForResource('tag.name', $resourceType, default: Str::ucfirst(Str::camel($resourceType)));
        if ($this->tags->has($name)) {
            return $name;
        }

        $this->tags->put($name, new Tag([
            'name' => $name,
            'description' => $this->getTranslationForResource(
                'tag.description',
                $resourceType,
                ['name' => $name],
                default: $this->getTranslation(
                    'tags.description',
                    ['name' => $name],
                ),
            ),
        ]));

        return $name;
    }

    public function add(string $name): string
    {
        $resolvedName = $this->getTranslation("tags.{$name}.name", ['name' => $name], default: $name);

        if ($this->tags->has($resolvedName)) {
            return $resolvedName;
        }

        $this->tags->put($resolvedName, new Tag([
            'name' => $resolvedName,
            'description' => $this->getTranslation(
                "tags.{$name}.description",
                ['name' => $resolvedName],
                default: $this->getTranslation(
                    'tags.description',
                    ['name' => $resolvedName],
                ),
            ),
        ]));

        return $resolvedName;
    }

    /**
     * @return array<Tag>
     */
    public function build(): array
    {
        return $this->tags->sortKeys()->values()->all();
    }
}
