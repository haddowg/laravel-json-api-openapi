<?php

namespace haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions;

use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses as SpecResponses;
use haddowg\JsonApiOpenApi\Enums\DefaultResponse;
use haddowg\JsonApiOpenApi\Enums\DefaultSchema;
use haddowg\JsonApiOpenApi\Factories\ResourceSchemaFactory;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation;

readonly class UpdatingRelationship extends Operation
{
    protected function responses(): SpecResponses
    {
        $responses = new SpecResponses([]);

        $responses->addResponse('204', $this->toResponseDefaultIfMatching(new Response([
            'description' => $this->getTranslationForResource(
                "actions.{$this->actionName()}.responses.204.description",
                default: $this->getTranslationForResource("actions.{$this->defaultActionName()}.responses.204.description"),
            ),
            'headers' => $this->builder()->components()->getRateLimitHeaders(),
        ]), DefaultResponse::UpdatingRelationship_204));

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
        ]), DefaultResponse::UpdatingRelationship_403));

        $this->addCommonDefaultResponses($responses);

        return $responses;
    }

    protected function requestBody(): RequestBody
    {
        $schema = $this->route->relation()->toOne() ? ResourceSchemaFactory::make($this->route)->getResourceLinkageSchema($this->route->resourceType())
            : [
                'type' => 'array',
                'items' => ResourceSchemaFactory::make($this->route)->getResourceLinkageSchema($this->route->resourceType()),
            ];

        return new RequestBody([
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => $schema,
                        ],
                        'required' => ['data'],
                    ],
                ],
            ],
        ]);
    }

    protected function defaultActionMethod(): string
    {
        return 'updateRelationship';
    }
}
