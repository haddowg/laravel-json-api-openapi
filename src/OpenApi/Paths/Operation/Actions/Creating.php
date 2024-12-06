<?php

namespace haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions;

use cebe\openapi\spec\Example;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses as SpecResponses;
use haddowg\JsonApiOpenApi\Attributes\Actions\Create;
use haddowg\JsonApiOpenApi\Enums\DefaultResponse;
use haddowg\JsonApiOpenApi\Enums\DefaultSchema;
use haddowg\JsonApiOpenApi\Factories\ResourceSchemaFactory;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation;
use Illuminate\Support\Collection;

readonly class Creating extends Operation
{
    protected function responses(): SpecResponses
    {
        $responses = new SpecResponses([]);

        $examples = $this->examples();

        $resourceAction = $this->route->resourceAction();
        $resourceAction = $resourceAction instanceof Create ? $resourceAction : null;
        $successResponses = $resourceAction ? $resourceAction->responseCodes : [201];

        if (in_array(201, $successResponses)) {
            $responses->addResponse('201', new Response([
                'description' => $this->getTranslationForResource(
                    "actions.{$this->actionName()}.responses.201.description",
                    default: $this->getTranslationForResource("actions.{$this->defaultActionName()}.responses.201.description"),
                ),
                'headers' => $this->builder()->components()->getRateLimitHeaders(),
                'content' => [
                    'application/vnd.api+json' => array_filter([
                        'schema' => ResourceSchemaFactory::make($this->route)->oneResourceSchema($resourceAction->returnResourceType ?? $this->route->resourceType()),
                        'examples' => $examples->count() > 1 ? $examples->all() : null,
                        'example' => $examples->isNotEmpty() ? $examples->first() : null,
                    ]),
                ],
            ]));
        }

        foreach ([202, 204] as $successResponse) {
            if (in_array($successResponse, $successResponses)) {
                $responses->addResponse((string) $successResponse, new Response(array_filter([
                    'description' => $this->getTranslationForResource(
                        "actions.{$this->actionName()}.responses.{$successResponse}.description",
                        default: $this->getTranslationForResource("actions.{$this->defaultActionName()}.responses.{$successResponse}.description"),
                    ),
                    'headers' => $this->builder()->components()->getRateLimitHeaders(),
                ])));
            }
        }

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
        ]), DefaultResponse::Creating_403));

        $responses->addResponse('404', $this->toResponseDefaultIfMatching(new Response([
            'description' => $this->getTranslationForResource(
                "actions.{$this->actionName()}.responses.404.description",
                default: $this->getTranslationForResource("actions.{$this->defaultActionName()}.responses.404.description"),
            ), 'headers' => $this->builder()->components()->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => $this->builder()->components()->getSchema(DefaultSchema::Failure),
                    'example' => $this->getTranslation('responses.404.example'),
                ],
            ],
        ]), DefaultResponse::Creating_404));

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
        ]), DefaultResponse::Creating_409));

        $this->addCommonDefaultResponses($responses);

        return $responses;
    }

    protected function requestBody(): RequestBody
    {
        $examples = $this->route->requestBodyExamples()->all();

        return new RequestBody([
            'content' => [
                'application/vnd.api+json' => array_filter([
                    'schema' => ResourceSchemaFactory::make($this->route)->storeResourceSchema($this->route->resourceType()),
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
        return 'store';
    }
}
