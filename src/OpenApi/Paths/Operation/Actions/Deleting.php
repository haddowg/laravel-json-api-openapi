<?php

namespace haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions;

use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses as SpecResponses;
use haddowg\JsonApiOpenApi\Enums\DefaultResponse;
use haddowg\JsonApiOpenApi\Enums\DefaultSchema;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation;

readonly class Deleting extends Operation
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
        ]), DefaultResponse::Deleting_204));

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
        ]), DefaultResponse::Deleting_404));

        $this->addCommonDefaultResponses($responses);

        return $responses;
    }

    protected function defaultActionMethod(): string
    {
        return 'destroy';
    }
}
