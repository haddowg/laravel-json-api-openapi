<?php

namespace haddowg\JsonApiOpenApi\Descriptors;

use haddowg\JsonApiOpenApi\Attributes\Actions\Create;
use haddowg\JsonApiOpenApi\Attributes\Actions\FetchMany;
use haddowg\JsonApiOpenApi\Attributes\Actions\FetchOne;
use haddowg\JsonApiOpenApi\Attributes\Actions\ResourceAction;
use haddowg\JsonApiOpenApi\Attributes\AttributeHelper;
use haddowg\JsonApiOpenApi\Attributes\Examples\CreateRequestBodyExample;
use haddowg\JsonApiOpenApi\Attributes\Examples\Example;
use haddowg\JsonApiOpenApi\Attributes\Examples\UpdateRequestBodyExample;
use haddowg\JsonApiOpenApi\Contracts\OpenApiBuilderContract as OpenApiBuilder;
use haddowg\JsonApiOpenApi\Descriptors\Pagination\Paginator;
use haddowg\JsonApiOpenApi\Descriptors\Validation\ValidationRules;
use haddowg\JsonApiOpenApi\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Schema\Relation;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Eloquent\Proxy;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

/**
 * @mixin \Illuminate\Routing\Route
 */
class Route
{
    use ForwardsCalls;

    public const ROUTE_PARAM_RESOURCE_TYPE = 'resource_type';
    public const ROUTE_PARAM_RESOURCE_ID_NAME = 'resource_id_name';
    public const ROUTE_PARAM_RESOURCE_RELATIONSHIP = 'resource_relationship';

    private ?string $resourceType = null;
    private ?ResourceAction $resourceAction = null;

    /**
     * @var array<class-string<ResourceAction>>
     */
    private array $actionAttributes = [
        FetchOne::class,
        FetchMany::class,
        Create::class,
    ];

    public function __construct(private readonly OpenApiBuilder $openApiBuilder, private readonly IlluminateRoute $route)
    {
        $this->detectResourceAction();
        if ($this->resourceType) {
            return;
        }

        $this->resourceType = $this->routeResourceType();

        if ($this->hasRelation()) {
            $this->resourceType = $this->relation()->inverse();
        }
    }

    public function __get(string $name): mixed
    {
        return $this->route->{$name};
    }

    /**
     * @param mixed $name
     * @param mixed $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->forwardCallTo($this->route, $name, $arguments);
    }

    public function schema(): ?Schema
    {
        if ($resourceType = $this->resourceType()) {
            return $this->openApiBuilder->server()->schemas()->schemaFor($resourceType);
        }

        return null;
    }

    public function method(): string
    {
        return strtolower(collect($this->route->methods())->filter(fn ($method) => $method !== 'HEAD')->first());
    }

    /**
     * @return Collection<string,\cebe\openapi\spec\Example>
     */
    public function examples(): Collection
    {
        $examples = collect();

        // Specific example from the controller
        if ($exampleAttributes = $this->getControllerAttributes(Example::class)) {
            foreach ($exampleAttributes as $exampleAttribute) {
                $examples->put($exampleAttribute->name(), $exampleAttribute->example($this));
            }
        }

        // Specific example from the request
        if ($exampleAttributes = $this->getRequestAttributes(Example::class)) {
            foreach ($exampleAttributes as $exampleAttribute) {
                $examples->put($exampleAttribute->name(), $exampleAttribute->example($this));
            }
        }

        return $examples;
    }

    /**
     * @return Collection<string,\cebe\openapi\spec\Example>
     */
    public function requestBodyExamples(): Collection
    {
        $examples = collect();
        $attribute = match (true) {
            $this->isCreating() => CreateRequestBodyExample::class,
            $this->isUpdating() => UpdateRequestBodyExample::class,
            default => null,
        };

        if (!$attribute) {
            return $examples;
        }

        // Specific example from the controller
        if ($exampleAttributes = $this->getControllerAttributes($attribute)) {
            foreach ($exampleAttributes as $exampleAttribute) {
                $examples->put($exampleAttribute->name(), $exampleAttribute->example($this));
            }
        }

        // Specific example from the request
        if ($exampleAttributes = $this->getRequestAttributes($attribute)) {
            foreach ($exampleAttributes as $exampleAttribute) {
                $examples->put($exampleAttribute->name(), $exampleAttribute->example($this));
            }
        }

        return $examples;
    }

    public function request(): ResourceRequest|ResourceQuery|QueryParameters|null
    {
        $schema = $this->schema();

        if (!$schema) {
            return null;
        }
        $data = $this->requestBodyExamples()->first()->value ?? null;
        if ($this->hasResourceId()) {
            $resource = $this->resources()->single($this->routeResourceType());
            $this->route->setParameter($this->resourceIdName(), $resource->resource ?? $this->modelInstance());
        }

        if ($this->isCreatingOrUpdating() && !isset($data['type'])) {
            $data['type'] = $schema::type();
            if ($this->isCreating() && !isset($data['id'])) {
                $data['id'] = $this->resources()->single($this->routeResourceType())?->id();
            }
        }

        $body = $data ? json_encode(
            ['data' => $data],
        ) : null;
        $baseRequest = Request::createFromBase(
            \Symfony\Component\HttpFoundation\Request::create(
                $this->url(),
                method: $this->method(),
                content: $body ?: null,
            ),
        );
        $this->openApiBuilder->container()->instance('request', $baseRequest);

        return match (true) {
            $this->isFetchingMany() => ResourceQuery::queryMany($schema::type()),
            $this->isFetchingOne() => ResourceQuery::queryOne($schema::type()),
            default => ResourceRequest::forResourceIfExists($schema::type()),
        };
    }

    public function resources(): ResourceFactory
    {
        return ResourceFactory::make($this);
    }

    public function validationRules(): ?ValidationRules
    {
        return ValidationRules::make($this);
    }

    public function resourceType(): ?string
    {
        return $this->resourceType;
    }

    public function routeResourceType(): ?string
    {
        return $this->route->originalParameter(self::ROUTE_PARAM_RESOURCE_TYPE);
    }

    public function resourceAction(): ?ResourceAction
    {
        return $this->resourceAction;
    }

    /**
     * @return class-string|null
     */
    public function modelClass(): ?string
    {
        // @phpstan-ignore-next-line return is a class-string
        return $this->schema()?->model();
    }

    public function modelInstance(): ?Model
    {
        if ($modelClass = $this->modelClass()) {
            return match (true) {
                is_a($modelClass, Model::class, true) => new $modelClass(),
                is_a($modelClass, Proxy::class, true) => (new $modelClass())->toBase(),
                default => null,
            };
        }

        return null;
    }

    public function routeSchema(): ?Schema
    {
        if ($resourceType = $this->routeResourceType()) {
            return $this->openApiBuilder->server()->schemas()->schemaFor($resourceType);
        }

        return null;
    }

    public function routeModelClass(): ?string
    {
        return $this->routeSchema()?->model();
    }

    public function routeModelInstance(): ?Model
    {
        if ($modelClass = $this->routeModelClass()) {
            return is_a($modelClass, Model::class, true) ?
                new $modelClass() :
                null;
        }

        return null;
    }

    public function hasResourceId(): bool
    {
        return !empty($this->resourceIdName());
    }

    public function doesntHaveResourceId(): bool
    {
        return !$this->hasResourceId();
    }

    /**
     * Get the relation for a relationship URL.
     */
    public function relation(): Relation
    {
        return $this->routeSchema()->relationship(
            $this->route->originalParameter(self::ROUTE_PARAM_RESOURCE_RELATIONSHIP),
        );
    }

    /**
     * Does the URL have a relation?
     */
    public function hasRelation(): bool
    {
        return (bool) $this->route->originalParameter(self::ROUTE_PARAM_RESOURCE_RELATIONSHIP);
    }

    public function getRoute(): IlluminateRoute
    {
        return $this->route;
    }

    public function paginator(): ?Paginator
    {
        if (!$this->isFetchingMany()) {
            return null;
        }
        $schema = $this->schema();
        $paginator = $schema?->pagination();
        if ($paginator === null) {
            return null;
        }

        return Paginator::make($this->openApiBuilder, $this, $paginator);
    }

    public function isFetchingMany(): bool
    {
        return $this->isViewingAny()
            || ($this->hasRelation() && $this->relation()->toMany() && $this->isViewingRelated());
    }

    public function isFetchingOne(): bool
    {
        return $this->isViewingOne()
            || ($this->hasRelation() && $this->relation()->toOne() && $this->isViewingRelated());
    }

    public function isViewingAny(): bool
    {
        return $this->resourceAction instanceof FetchMany
            || ($this->isMethod('GET') && $this->doesntHaveResourceId() && $this->isNotRelationship());
    }

    /**
     * Is this a request to view a specific resource? (Read action.).
     */
    public function isViewingOne(): bool
    {
        return $this->resourceAction instanceof FetchOne
            || ($this->isMethod('GET') && $this->hasResourceId() && $this->isNotRelationship());
    }

    /**
     * Is this a request to view related resources in a relationship? (Show-related action.).
     */
    public function isViewingRelated(): bool
    {
        return $this->isMethod('GET') && $this->isRelationship() && !$this->urlHasRelationships();
    }

    /**
     * Is this a request to view resource identifiers in a relationship? (Show-relationship action.).
     */
    public function isViewingRelationship(): bool
    {
        return $this->isMethod('GET') && $this->isRelationship() && $this->urlHasRelationships();
    }

    /**
     * Is this a request to create a resource?
     */
    public function isCreating(): bool
    {
        return $this->resourceAction instanceof Create
            || ($this->isMethod('POST') && $this->isNotRelationship() && !$this->hasResourceId());
    }

    /**
     * Is this a request to update a resource?
     */
    public function isUpdating(): bool
    {
        return $this->isMethod('PATCH') && $this->isNotRelationship() && $this->hasResourceId();
    }

    /**
     * Is this a route to create or update a resource?
     */
    public function isCreatingOrUpdating(): bool
    {
        return $this->isCreating() || $this->isUpdating();
    }

    /**
     * Is this a route to replace a resource relationship?
     */
    public function isUpdatingRelationship(): bool
    {
        return $this->isMethod('PATCH') && $this->isRelationship();
    }

    /**
     * Is this a route to attach records to a resource relationship?
     */
    public function isAttachingRelationship(): bool
    {
        return $this->isMethod('POST') && $this->isRelationship();
    }

    /**
     * Is this a route to detach records from a resource relationship?
     */
    public function isDetachingRelationship(): bool
    {
        return $this->isMethod('DELETE') && $this->isRelationship();
    }

    /**
     * Is this a route to modify a resource relationship?
     */
    public function isModifyingRelationship(): bool
    {
        return $this->isUpdatingRelationship()
            || $this->isAttachingRelationship()
            || $this->isDetachingRelationship();
    }

    /**
     * Is this a route to delete a resource?
     */
    public function isDeleting(): bool
    {
        return $this->isMethod('DELETE') && $this->isNotRelationship();
    }

    /**
     * Is this a route to view or modify a relationship?
     *
     * Alias for hasRelation()
     * @see hasRelation()
     */
    public function isRelationship(): bool
    {
        return $this->hasRelation();
    }

    /**
     * Is this a route not interacting with a relationship?
     */
    public function isNotRelationship(): bool
    {
        return !$this->isRelationship();
    }

    public function uri(): string
    {
        return '/' . Str::ltrim($this->uri, '/');
    }

    public function url(): string
    {
        $params = [];
        if ($this->hasResourceId()) {
            $resourceId = $this->resources()->single($this->routeResourceType())?->id();
            $segment = $this->route->originalParameter(self::ROUTE_PARAM_RESOURCE_ID_NAME);
            $params = $resourceId ? [$segment => $resourceId] : [$segment => "{$segment}"];
        }

        return Str::rtrim(URL::route($this->route->getName(), $params), '#');
    }

    /**
     * @template T
     * @param class-string<T> $attribute
     * @return array<T>|null
     */
    public function getRequestAttributes(string $attribute): ?array
    {
        if ($request = $this->requestClass()) {
            return AttributeHelper::getAttributes($request, $attribute);
        }

        return null;
    }

    /**
     * @template T
     * @param class-string<T> $attribute
     * @return array<T>|null
     */
    public function getControllerAttributes(string $attribute): ?array
    {
        /** @var class-string|null $controller */
        $controller = $this->getControllerClass();
        if (!$controller) {
            return null;
        }

        if ($this->getActionMethod() === '__invoke') {
            return AttributeHelper::getAttributes($controller, $attribute);
        }

        return AttributeHelper::getAttributes($controller, $attribute, $this->getActionMethod());
    }

    /**
     * @return class-string|null
     */
    protected function requestClass(): ?string
    {
        $schema = $this->schema();

        if (!$schema) {
            return null;
        }
        $request = match (true) {
            $this->isFetchingMany() => ResourceQuery::queryMany($schema::type()),
            $this->isFetchingOne() => ResourceQuery::queryOne($schema::type()),
            default => ResourceRequest::forResourceIfExists($schema::type()),
        };

        return $request ? get_class($request) : null;
    }

    protected function isMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods());
    }

    private function resourceIdName(): ?string
    {
        return $this->route->originalParameter(self::ROUTE_PARAM_RESOURCE_ID_NAME) ?: null;
    }

    private function detectResourceAction(): void
    {
        /** @var class-string|null $controller */
        $controller = $this->getControllerClass();
        if (!$controller) {
            return;
        }

        if ($this->route->getActionMethod() === '__invoke') {
            foreach ($this->actionAttributes as $actionAttribute) {
                if ($attribute = AttributeHelper::getAttribute($controller, $actionAttribute)) {
                    $this->resourceAction = $attribute;
                    $this->resourceType = $this->resourceAction->resourceType ?? null;

                    return;
                }
            }
        }

        foreach ($this->actionAttributes as $actionAttribute) {
            if ($attribute = AttributeHelper::getAttribute($controller, $actionAttribute, $this->route->getActionMethod())) {
                $this->resourceAction = $attribute;
                $this->resourceType = $this->resourceAction->resourceType ?? null;

                return;
            }
        }
    }

    /**
     * Does the URL contain the keyword "relationships".
     */
    private function urlHasRelationships(): bool
    {
        return Str::contains($this->uri(), 'relationships');
    }
}
