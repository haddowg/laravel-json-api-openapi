<?php

namespace haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions;

use cebe\openapi\spec\Example;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses as SpecResponses;
use cebe\openapi\spec\Schema;
use haddowg\JsonApiOpenApi\Descriptors\Link;
use haddowg\JsonApiOpenApi\Enums\DefaultResponse;
use haddowg\JsonApiOpenApi\Enums\DefaultSchema;
use haddowg\JsonApiOpenApi\Factories\ResourceSchemaFactory;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation;
use Illuminate\Contracts\Support\Responsable;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Core\Responses\Internal\PaginatedIdentifierResponse;
use LaravelJsonApi\Core\Responses\Internal\ResourceIdentifierCollectionResponse;
use LaravelJsonApi\Core\Responses\Internal\ResourceIdentifierResponse;

readonly class ViewingRelationship extends Operation
{
    protected function responses(): SpecResponses
    {
        $responses = new SpecResponses([]);

        if ($this->route->relation()->toOne()) {
            $responses->addResponse('200', $this->toOne());
        } else {
            $responses->addResponse('200', $this->toMany());
        }
        // TODO can we detect if a 401 response is needed based on auth middleware?
        // TODO can we detect if a 403 response is needed based on policy or attributes?
        $responses->addResponse('404', $this->toResponseDefaultIfMatching(new Response([
            'description' => $this->getTranslationForResource(
                "actions.{$this->actionName()}.responses.404.description",
                default: $this->getTranslationForResource("actions.{$this->defaultActionName()}.responses.404.description"),
            ),
            'headers' => $this->builder()->components()->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => $this->builder()->components()->getSchema(DefaultSchema::Failure),
                    'example' => $this->getTranslation('responses.404.example'),
                ],
            ],
        ]), DefaultResponse::ViewingRelationship_404));

        $this->addCommonDefaultResponses($responses);

        return $responses;
    }

    protected function toOne(): Response
    {
        $resourceType = $this->route->resourceType();

        $resource = $this->route->resources()->single();
        $parentResource = $this->route->resources()->single($this->route->routeResourceType());

        $examples = $this->examples();

        $meta = $this->builder()->components()->getSchema(DefaultSchema::Meta);
        if ($resource && $parentResource) {
            $response = new ResourceIdentifierResponse($parentResource, $this->route->relation()->name(), $resource->resource);
            $meta = $this->getRelationMeta($response) ?? $meta;
            if ($examples->isEmpty()) {
                $examples->put('example', new Example(['value' => $this->responsableToJson($response)]));
            }
        }

        $links = $this->builder()->components()->getSchema(DefaultSchema::Links);
        if ($relationLinks = $this->getRelationLinks()) {
            $links = [
                'allOf' => [
                    $links,
                    $relationLinks,
                ],
            ];
        }

        return new Response([
            'description' => $this->getTranslationForResource(
                "actions.{$this->actionName()}.responses.200.description",
                default: $this->getTranslationForResource("actions.{$this->defaultActionName()}.responses.200.description"),
            ),
            'headers' => $this->builder()->components()->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => array_filter([
                    'schema' => [
                        'title' => $this->getTranslationForResource('schema.relationship.description.toOne', $resourceType),
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'oneOf' => [
                                    ['type' => 'null'],
                                    ResourceSchemaFactory::make($this->route)->getResourceLinkageSchema($resourceType),
                                ],
                            ],
                            'links' => $links,
                            'meta' => $meta,
                            'jsonapi' => $this->builder()->components()->getSchema(DefaultSchema::JsonApi),
                        ],
                    ],
                    'examples' => $examples->count() > 1 ? $examples->all() : null,
                    'example' => $examples->isNotEmpty() ? $examples->first() : null,
                ]),
            ],
        ]);
    }

    protected function toMany(): Response
    {
        $resourceType = $this->route->resourceType();

        $paginator = $this->route->paginator();

        $examples = $this->examples();

        $parentResource = $this->route->resources()->single($this->route->routeResourceType());
        $data = $paginator ? $paginator->paginate() : $this->route->resources()->collection();

        $meta = $this->builder()->components()->getSchema(DefaultSchema::Meta);

        if ($data->count() > 0 && $parentResource) {
            $response = $data instanceof Page ? new PaginatedIdentifierResponse($parentResource, $this->route->relation()->name(), $data) :
                new ResourceIdentifierCollectionResponse($parentResource, $this->route->relation()->name(), $data);
            $meta = $this->getRelationMeta($response) ?? $meta;
            if ($examples->isEmpty()) {
                $examples->put('example', new Example(['value' => $this->responsableToJson($response)]));
            }
        }

        $links = $paginator ? $paginator->links() : $this->builder()->components()->getSchema(DefaultSchema::Links);
        if ($relationLinks = $this->getRelationLinks()) {
            $links = [
                'allOf' => [
                    $links,
                    $relationLinks,
                ],
            ];
        }

        return new Response([
            'description' => $this->getTranslationForResource(
                "actions.{$this->actionName()}.responses.200.description",
                default: $this->getTranslationForResource("actions.{$this->defaultActionName()}.responses.200.description"),
            ),
            'headers' => $this->builder()->components()->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => array_filter([
                    'schema' => [
                        'title' => $this->getTranslationForResource('schema.relationship.description.toMany', $resourceType),
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'array',
                                'items' => ResourceSchemaFactory::make($this->route)->getResourceLinkageSchema($resourceType),
                                'uniqueItems' => true,
                            ],
                            'links' => $links,
                            'meta' => $meta,
                            'jsonapi' => $this->builder()->components()->getSchema(DefaultSchema::JsonApi),
                        ],
                    ],
                    'examples' => $examples->count() > 1 ? $examples->all() : null,
                    'example' => $examples->isNotEmpty() ? $examples->first() : null,
                ]),
            ],
        ]);
    }

    protected function getRelationMeta(?Responsable $response): ?Schema
    {
        if (!$response) {
            return null;
        }

        $method = $response instanceof PaginatedIdentifierResponse ? 'meta' : 'allMeta';
        $response = invade($response);
        $meta = $response->{$method}()?->toArray();

        if (empty($meta)) {
            return null;
        }

        $schema = $this->toObjectSchema($meta, true);
        if ($description = $this->getTranslation('schema.meta')) {
            $schema->description = $description;
        }

        return $schema;
    }

    protected function getRelationLinks(): ?Schema
    {
        $resource = $this->route->resources()->single($this->route->routeResourceType());

        $self = new Schema([
            'type' => 'object',
            'properties' => [
                'self' => $this->getSelfLink(),
            ],
        ]);

        if (!$resource) {
            return $self;
        }

        try {
            $relationLinks = $resource->relationship($this->route->relation()->name())->links();

            $links = [];

            if ($relationLinks->hasRelated()) {
                $links['related'] = Link::make($relationLinks->getRelated());
            }

            if ($relationLinks->hasSelf()) {
                $links['self'] = Link::make($relationLinks->getSelf());
            }
        } catch (\LogicException $e) {
            return $self;
        }

        if (empty($links)) {
            return $self;
        }

        return new Schema([
            'type' => 'object',
            'properties' => $links,
        ]);
    }

    protected function defaultActionMethod(): string
    {
        return 'showRelationship';
    }
}
