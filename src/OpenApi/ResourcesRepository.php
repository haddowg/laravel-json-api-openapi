<?php

namespace haddowg\JsonApiOpenApi\OpenApi;

use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class ResourcesRepository
{
    /**
     * @var Collection<string, Collection<int,JsonApiResource>>
     */
    private Collection $resources;

    public function __construct()
    {
        $this->resources = Collection::empty();
    }

    public function single(string $resourceType): ?JsonApiResource
    {
        return $this->resources->get($resourceType)?->first();
    }

    /**
     * @return Collection<int,JsonApiResource>
     */
    public function collection(string $resourceType, int $count): Collection
    {
        return $this->resources->get($resourceType)?->take($count) ?? Collection::empty();
    }

    public function has(string $resourceType, ?int $count = 1): bool
    {
        $count ??= 1;

        return $this->resources->has($resourceType) && $this->resources->get($resourceType)->count() >= $count;
    }

    public function add(string $resourceType, JsonApiResource ...$resources): void
    {
        if (!$this->resources->has($resourceType)) {
            $this->resources->put($resourceType, Collection::empty());
        }
        $this->resources->get($resourceType)->push(...$resources);
    }
}
