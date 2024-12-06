<?php

namespace haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions;

use cebe\openapi\spec\Example;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses as SpecResponses;
use cebe\openapi\spec\Schema;
use haddowg\JsonApiOpenApi\Enums\DefaultResponse;
use haddowg\JsonApiOpenApi\Enums\DefaultSchema;
use haddowg\JsonApiOpenApi\Factories\ResourceSchemaFactory;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation;
use Illuminate\Contracts\Support\Responsable;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Core\Responses\Internal\PaginatedRelatedResourceResponse;
use LaravelJsonApi\Core\Responses\Internal\RelatedResourceCollectionResponse;
use LaravelJsonApi\Core\Responses\Internal\RelatedResourceResponse;

readonly class ViewingRelated extends Operation
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
        ]), DefaultResponse::ViewingRelated_404));

        $this->addCommonDefaultResponses($responses);

        return $responses;
    }

    protected function toOne(): Response
    {
        $resourceType = $this->route->resourceType();
        $resource = $this->route->resources()->single();
        $parentResource = $this->route->resources()->single($this->route->routeResourceType());

        $meta = $this->builder()->components()->getSchema(DefaultSchema::Meta);

        $examples = $this->examples();
        if ($resource && $parentResource) {
            $response = new RelatedResourceResponse($parentResource, $this->route->relation()->name(), $resource->resource);
            $meta = $this->getRelationMeta($response) ?? $meta;
            if ($examples->isEmpty()) {
                $examples->put('example', new Example(['value' => $this->responsableToJson($response)]));
            }
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
                                    ResourceSchemaFactory::make($this->route)->resourceSchema($resourceType),
                                ],
                            ],
                            'links' => [
                                'allOf' => [
                                    $this->builder()->components()->getSchema(DefaultSchema::Links),
                                    $this->getSelfLink(),
                                ],
                            ],
                            'meta' => $meta,
                            'jsonapi' => $this->builder()->components()->getSchema(DefaultSchema::JsonApi),
                        ],
                        'required' => ['data', 'links', 'meta', 'jsonapi'],
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

        $parentResource = $this->route->resources()->single($this->route->routeResourceType());
        $data = $paginator ? $paginator->paginate() : $this->route->resources()->collection();

        $meta = $this->builder()->components()->getSchema(DefaultSchema::Meta);

        $examples = $this->examples();
        if ($data->count() > 0 && $parentResource) {
            $response = $data instanceof Page ? new PaginatedRelatedResourceResponse($parentResource, $this->route->relation()->name(), $data) :
                new RelatedResourceCollectionResponse($parentResource, $this->route->relation()->name(), $data);
            $meta = $this->getRelationMeta($response) ?? $meta;
            if ($examples->isEmpty()) {
                $examples->put('example', new Example(['value' => $this->responsableToJson($response)]));
            }
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
                                'items' => ResourceSchemaFactory::make($this->route)->resourceSchema($resourceType),
                                'uniqueItems' => true,
                            ],
                            'links' => [
                                'allOf' => [
                                    $paginator ? $paginator->links() : $this->builder()->components()->getSchema(DefaultSchema::Links),
                                    $this->getSelfLink(),
                                ],
                            ],
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

        $method = $response instanceof PaginatedRelatedResourceResponse ? 'meta' : 'allMeta';
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

    protected function defaultActionMethod(): string
    {
        return 'showRelated';
    }
}
