<?php

namespace haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions;

use cebe\openapi\spec\Example;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses as SpecResponses;
use haddowg\JsonApiOpenApi\Enums\DefaultResponse;
use haddowg\JsonApiOpenApi\Enums\DefaultSchema;
use haddowg\JsonApiOpenApi\Factories\ResourceSchemaFactory;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation;
use Illuminate\Support\Collection;

readonly class Updating extends Operation
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
                    'schema' => ResourceSchemaFactory::make($this->route)->oneResourceSchema($this->route->resourceType()),
                    'examples' => $examples->count() > 1 ? $examples->all() : null,
                    'example' => $examples->isNotEmpty() ? $examples->first() : null,
                ]),
            ],
        ]));

        // TODO can we detect if a 401 response is needed based on auth middleware?
        $responses->addResponse('403', $this->toResponseDefaultIfMatching(new Response([
            'description' => $this->getTranslationForResource(
                "actions.{$this->actionName()}.responses.403.description",
                default: $this->getTranslationForResource("actions.{$this->defaultActionName()}.responses.403.description"),
            ),
            'headers' => $this->builder()->components()->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => $this->builder()->components()->getSchema(DefaultSchema::Failure),
                    'example' => $this->getTranslation('responses.403.example'),
                ],
            ],
        ]), DefaultResponse::Updating_403));

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
        ]), DefaultResponse::Updating_404));

        $responses->addResponse('409', $this->toResponseDefaultIfMatching(new Response([
            'description' => $this->getTranslationForResource(
                "actions.{$this->actionName()}.responses.409.description",
                default: $this->getTranslationForResource("actions.{$this->defaultActionName()}.responses.409.description"),
            ),
            'headers' => $this->builder()->components()->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => $this->builder()->components()->getSchema(DefaultSchema::Failure),
                    'example' => $this->getTranslation('responses.409.example'),
                ],
            ],
        ]), DefaultResponse::Updating_409));

        $this->addCommonDefaultResponses($responses);

        return $responses;
    }

    protected function requestBody(): RequestBody
    {
        $examples = $this->route->requestBodyExamples()->all();

        return new RequestBody([
            'content' => [
                'application/vnd.api+json' => array_filter([
                    'schema' => ResourceSchemaFactory::make($this->route)->updateResourceSchema($this->route->resourceType()),
                    'examples' => !empty($examples) ? $examples : null,
                ]),
            ],
        ]);
    }

    protected function examples(): Collection
    {
        $examples = parent::examples();

        if ($examples->isNotEmpty()) {
            return $examples;
        }

        $resource = $this->route->resources()->single();

        if ($resource) {
            $examples->put('example', new Example(['value' => $this->responsableToJson($resource)]));
        }

        return $examples;
    }

    protected function defaultActionMethod(): string
    {
        return 'update';
    }
}
