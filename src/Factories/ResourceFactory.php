<?php

namespace haddowg\JsonApiOpenApi\Factories;

use haddowg\JsonApiOpenApi\Concerns\BuilderAware;
use haddowg\JsonApiOpenApi\Contracts\HasFactory;
use haddowg\JsonApiOpenApi\Contracts\ResourceFactory as ResourceFactoryContract;
use haddowg\JsonApiOpenApi\Descriptors\Route;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Resources\JsonApiResource;
use LaravelJsonApi\Eloquent\Proxy;
use LaravelJsonApi\Eloquent\Schema;

class ResourceFactory
{
    use BuilderAware;

    public function __construct(protected readonly Route $route) {}

    public static function make(Route $route): self
    {
        return new self($route);
    }

    public function single(?string $resourceType = null): ?JsonApiResource
    {
        $resourceType ??= $this->route->resourceType();

        if ($factory = $this->factory($resourceType)) {
            return $factory->single();
        }

        if ($this->builder()->resources()->has($resourceType)) {
            return $this->builder()->resources()->single($resourceType);
        }

        return $this->create($resourceType)->first();
    }

    /**
     * @return Collection<int,JsonApiResource>
     */
    public function collection(int $count = 3, ?string $resourceType = null): Collection
    {
        $resourceType ??= $this->route->resourceType();

        if ($factory = $this->factory($resourceType)) {
            return collect($factory->collection($count));
        }

        $existing = $this->builder()->resources()->collection($resourceType, $count);
        $diff = $count - $existing->count();
        if ($diff === 0) {
            return $existing;
        }

        return $existing->merge($this->create($resourceType, $diff));
    }

    protected function factory(string $resourceType): ?ResourceFactoryContract
    {
        $schema = $this->builder()->server()->schemas()->schemaFor($resourceType);
        if ($schema instanceof HasFactory) {
            return $schema->factory($this->route);
        }

        return null;
    }

    /**
     * @return Collection<int,JsonApiResource>
     */
    protected function create(string $resourceType, int $count = 1): Collection
    {
        $schema = $this->builder()->server()->schemas()->schemaFor($resourceType);
        $resource = $schema::resource();

        $resources = Collection::empty();
        if ($schema instanceof Schema) {
            $class = $schema::model();
            $modelInstance = new $class();
            $model = $modelInstance;
            if ($modelInstance instanceof Proxy) {
                $model = $modelInstance->toBase();
            }
            try {
                if (method_exists($model, 'factory')) {
                    $factory = $model->factory();
                    if ($factory instanceof Factory) {
                        // @phpstan-ignore-next-line count forces a collection
                        $resources = $factory->count($count)
                            ->create()
                            ->values()
                            ->map(
                                fn ($model) => new $resource($schema, $modelInstance instanceof \LaravelJsonApi\Eloquent\Contracts\Proxy ? $modelInstance::proxyFor($model) : $model),
                            )->toBase();
                    }
                }
            } catch (\Throwable $ex) {
                // noop
            }
            $this->builder()->resources()->add($resourceType, ...$resources);
        }

        return $resources;
    }
}
