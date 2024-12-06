<?php

namespace haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions;

use cebe\openapi\spec\Example;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses as SpecResponses;
use haddowg\JsonApiOpenApi\Factories\ResourceSchemaFactory;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Resources\ResourceCollection;
use LaravelJsonApi\Core\Responses\Internal\PaginatedResourceResponse;
use LaravelJsonApi\Core\Responses\Internal\ResourceCollectionResponse;

readonly class ViewingAny extends Operation
{
    protected function responses(): SpecResponses
    {
        $responses = new SpecResponses([]);

        $examples = $this->examples();

        $responses->addResponse('200', new Response([
            'description' => $this->getTranslationForResource(
                "actions.{$this->actionName()}.responses.200.description",
                default: $this->getTranslationForResource("actions.{$this->defaultActionName()}.responses.200.description"),
            ),
            'headers' => $this->builder()->components()->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => array_filter([
                    'schema' => ResourceSchemaFactory::make($this->route)->manyResourceSchema($this->route->resourceType()),
                    'examples' => $examples->count() > 1 ? $examples->all() : null,
                    'example' => $examples->isNotEmpty() ? $examples->first() : null,
                ]),
            ],
        ]));
        // TODO can we detect if a 401 response is needed based on auth middleware?
        // TODO can we detect if a 403 response is needed based on policy or attributes?
        $this->addCommonDefaultResponses($responses);

        return $responses;
    }

    protected function examples(): Collection
    {
        $examples = parent::examples();

        if ($examples->isNotEmpty()) {
            return $examples;
        }

        if ($paginator = $this->route->paginator()) {
            if ($paginator->isDefault()) {
                $page = $paginator->paginate();
                $examples->put('example', new Example(['value' => $this->responsableToJson(new PaginatedResourceResponse($page))]));

                return $examples;
            }
        }

        $resources = $this->route->resources()->collection();
        $examples->put('example', new Example(['value' => $this->responsableToJson(new ResourceCollectionResponse(new ResourceCollection($resources)))]));

        return $examples;
    }

    protected function defaultActionMethod(): string
    {
        return 'index';
    }
}
