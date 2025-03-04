<?php

namespace haddowg\JsonApiOpenApi\OpenApi\Paths\Operation;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use haddowg\JsonApiOpenApi\Concerns\PerformsTranslationForRoute;
use haddowg\JsonApiOpenApi\Descriptors\Filters;
use haddowg\JsonApiOpenApi\Descriptors\Id;
use haddowg\JsonApiOpenApi\Descriptors\Route;
use Illuminate\Support\Str;
use LaravelJsonApi\Core\Support\Str as JsonApiStr;

readonly class Parameters
{
    use PerformsTranslationForRoute;

    public function __construct(protected Route $route) {}

    public static function make(Route $route): self
    {
        return new self($route);
    }

    /**
     * @return array<Parameter|Reference>
     */
    public function build(): array
    {
        return [
            ...$this->filters(),
            ...$this->sorting(),
            ...$this->includes(),
            ...$this->pagination(),
            ...$this->fields(),
        ];
    }

    public function resourceId(): Reference|Parameter|null
    {
        if ($this->route->hasResourceId()) {
            $example = $this->route->resources()->single($this->route->routeResourceType())?->id();

            $name = Str::camel($this->route->originalParameter($this->route::ROUTE_PARAM_RESOURCE_ID_NAME)) . '_Id';

            $id = $this->route->schema()?->id();
            $parameter = [
                'name' => $this->route->parameter($this->route::ROUTE_PARAM_RESOURCE_ID_NAME),
                'description' => $this->getTranslationForResource('parameters.resource-id', $this->route->routeResourceType(), default: $this->getTranslation('parameters.resource-id')),
                'in' => 'path',
                'required' => true,
                'allowEmptyValue' => false,
                'schema' => $id ? Id::make($this->builder(), $id, false) : ['type' => 'string'],
            ];

            if ($example) {
                $parameter['example'] = $example;
            }

            $parameter = new Parameter($parameter);
            $parameterName = Str::camel(JsonApiStr::singular($this->route->routeResourceType()));

            dump($this->route->parameter($this->route::ROUTE_PARAM_RESOURCE_ID_NAME), $parameterName);
            if ($this->route->parameter($this->route::ROUTE_PARAM_RESOURCE_ID_NAME) !== $parameterName) {
                return $parameter;
            }

            return $this->builder()->components()->addParameter($name, $parameter);
        }

        return null;
    }

    /**
     * @return array<Parameter>
     */
    protected function pagination(): array
    {
        if (!$this->route->isFetchingMany()) {
            return [];
        }

        $paginator = $this->route->paginator();

        return $paginator ? $paginator->parameters() : [];
    }

    /**
     * @return array<int, Parameter>
     */
    protected function sorting(): array
    {
        if (!$this->route->isFetchingMany()) {
            return [];
        }
        $schema = $this->route->schema();
        if (!$schema) {
            return [];
        }

        $sortable = $this->route->validationRules()->sortable();
        // sort is not supported
        if ($sortable === null) {
            return [];
        }
        // no validation of sort on request so we use the schema
        if ($sortable->isEmpty()) {
            /** @var iterable<int, string> $sortFields */
            $sortFields = $schema->sortFields();
            $sortable = collect($sortFields);
        }

        $sortKeys = $sortable
            ->map(function (string $field) {
                return [$field, '-' . $field];
            })
            ->flatten()
            ->toArray();

        if (empty($sortKeys)) {
            return [];
        }

        return [
            new Parameter([
                'name' => 'sort',
                'in' => 'query',
                'description' => $this->getTranslationForResource('parameters.sort', default: $this->getTranslation('parameters.sort')),
                'required' => false,
                'allowEmptyValue' => false,
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => $sortKeys,
                    ],
                    'example' => [$sortKeys[0]],
                ],
                'explode' => false,
                'style' => 'form',
            ]),
        ];
    }

    /**
     * @return array<int, Parameter|Reference>
     */
    protected function filters(): array
    {
        return Filters::make($this->route)->build();
    }

    /**
     * @return array<int, Parameter>
     */
    protected function includes(): array
    {
        if ($this->route->isRelationship() && !$this->route->isViewingRelated()) {
            return [];
        }

        $schema = $this->route->schema();
        if (!$schema) {
            return [];
        }

        /** @var iterable<int,string> $includePaths */
        $includePaths = $schema->includePaths();
        if (iterator_count($includePaths) === 0) {
            return [];
        }

        $includes = $this->route->validationRules()->includes();

        // sort is not supported
        if ($includes === null) {
            return [];
        }
        // no validation of sort on request so we use the schema
        if ($includes->isEmpty()) {
            $includes = collect($includePaths);
            if ($includes->isEmpty()) {
                return [];
            }
        }

        $includes = $includes->sort()->values();

        return [
            new Parameter([
                'name' => 'includes',
                'in' => 'query',
                'description' => $this->getTranslationForResource('parameters.sort', default: $this->getTranslation('parameters.sort')),
                'required' => false,
                'allowEmptyValue' => false,
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => $includes->all(),
                    ],
                    'example' => [$includes->take(2)->implode(',')],
                ],
                'explode' => false,
                'style' => 'form',
            ]),
        ];
    }

    /**
     * @return array<Parameter|Reference>
     */
    protected function fields(): array
    {
        if ($this->route->isRelationship() && !$this->route->isViewingRelated()) {
            return [];
        }

        $schema = $this->route->schema();
        if (!$schema || $this->route->validationRules()->isNotSupported('fields')) {
            return [];
        }

        return [
            $this->builder()->components()->getParameter('JsonApi:fieldsParameter'),
        ];
    }
}
