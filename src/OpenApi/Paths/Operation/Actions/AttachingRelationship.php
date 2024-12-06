<?php

namespace haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions;

use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses as SpecResponses;
use haddowg\JsonApiOpenApi\Enums\DefaultResponse;
use haddowg\JsonApiOpenApi\Enums\DefaultSchema;

readonly class AttachingRelationship extends UpdatingRelationship
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
        ]), DefaultResponse::AttachingRelationship_204));

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
        ]), DefaultResponse::AttachingRelationship_403));

        $this->addCommonDefaultResponses($responses);

        return $responses;
    }

    protected function defaultActionMethod(): string
    {
        return 'attachRelationship';
    }
}
