<?php

namespace haddowg\JsonApiOpenApi\Attributes\Examples;

use cebe\openapi\spec\Example as SpecExample;
use haddowg\JsonApiOpenApi\Descriptors\Route;
use haddowg\JsonApiOpenApi\Facades\JsonApiOpenApi;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Core\Resources\JsonApiResource;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Example
{
    public function __construct(private readonly string $name, private readonly mixed $example, private readonly ?string $resourceType = null) {}

    public function name(): string
    {
        return $this->name;
    }

    public function example(Route $route, ?string $resourceType = null): SpecExample
    {
        $resourceType ??= $this->resourceType ?? $route->resourceType();
        $example = value($this->example, $route, $resourceType);

        return match (true) {
            $example instanceof SpecExample => $example,
            $example instanceof Factory => $this->fromFactory($example, $this->resourceType),
            $example instanceof Model => $this->fromModel($example, $this->resourceType),
            $example instanceof Responsable => new SpecExample([
                'value' => $this->responsableToJson($example),
            ]),
            default => new SpecExample([
                // @phpstan-ignore-next-line types are unknown but we don't care
                'value' => collect($example)->toArray(),
            ]),
        };
    }

    /**
     * @param Factory<Model> $factory
     */
    protected function fromFactory(Factory $factory, ?string $resourceType): SpecExample
    {
        return $this->fromModel($factory->createOne(), $resourceType);
    }

    protected function fromModel(Model $model, ?string $resourceType): SpecExample
    {
        $schema = $resourceType ?
            JsonApiOpenApi::builder()->server()->schemas()->schemaFor($resourceType) :
            JsonApiOpenApi::builder()->server()->schemas()->schemaForModel($model);
        $schemaModel = $schema::model();

        if (!$model instanceof $schemaModel) {
            throw new \InvalidArgumentException('Model does not match schema');
        }

        /** @var class-string<JsonApiResource> $resource */
        $resource = $schema::resource();

        return new SpecExample([
            'value' => $this->responsableToJson(new $resource($schema, $model)),
        ]);
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function responsableToJson(Responsable $responsable): array
    {
        return json_decode((string) $responsable->toResponse(request())->getContent(), true);
    }
}
