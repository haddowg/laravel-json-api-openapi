<?php

namespace haddowg\JsonApiOpenApi\OpenApi\Paths;

use cebe\openapi\spec\Example;
use cebe\openapi\spec\Operation as SpecOperation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Responses as SpecResponses;
use cebe\openapi\spec\Schema;
use haddowg\JsonApiOpenApi\Concerns\InfersSchema;
use haddowg\JsonApiOpenApi\Concerns\PerformsTranslationForRoute;
use haddowg\JsonApiOpenApi\Descriptors\Link;
use haddowg\JsonApiOpenApi\Descriptors\Route;
use haddowg\JsonApiOpenApi\Enums\DefaultResponse;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions\AttachingRelationship;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions\Creating;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions\Deleting;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions\DetachingRelationship;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions\Updating;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions\UpdatingRelationship;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions\ViewingAny;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions\ViewingOne;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions\ViewingRelated;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Actions\ViewingRelationship;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Parameters;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract readonly class Operation
{
    use InfersSchema;
    use PerformsTranslationForRoute;

    public function __construct(
        protected Route $route,
    ) {}

    final public static function make(Route $route): ?Operation
    {
        return match (true) {
            $route->isViewingOne() => new ViewingOne($route),
            $route->isViewingAny() => new ViewingAny($route),
            $route->isCreating() => new Creating($route),
            $route->isUpdating() => new Updating($route),
            $route->isDeleting() => new Deleting($route),
            $route->isViewingRelated() => new ViewingRelated($route),
            $route->isViewingRelationship() => new ViewingRelationship($route),
            $route->isAttachingRelationship() => new AttachingRelationship($route),
            $route->isDetachingRelationship() => new DetachingRelationship($route),
            $route->isUpdatingRelationship() => new UpdatingRelationship($route),
            default => null,
        };
    }

    public function build(): SpecOperation
    {
        return new SpecOperation(array_filter([
            'operationId' => $this->route->getName(),
            'summary' => $this->getTranslationForResource("actions.{$this->actionName()}.summary"),
            'description' => $this->getTranslationForResource("actions.{$this->actionName()}.description"),
            'tags' => $this->tags(),
            'parameters' => $this->parameters(),
            'responses' => $this->responses(),
            'requestBody' => $this->requestBody(),
        ]));
    }

    protected function actionName(): string
    {
        return $this->route->getActionMethod() === $this->defaultActionMethod() ? $this->defaultActionName() : $this->route->getActionMethod();
    }

    abstract protected function defaultActionMethod(): string;

    protected function defaultActionName(): string
    {
        return Str::camel(class_basename(static::class));
    }

    /**
     * @return string[]
     */
    protected function tags(): array
    {
        $tags = [];
        // TODO: get tags from attributes on controller method or class
        // @phpstan-ignore-next-line
        if (!empty($tags)) {
            return $tags;
        }
        if ($type = $this->route->routeResourceType()) {
            $resourceTag = $this->builder()->tags()->addResourceType($type);

            return [$resourceTag];
        }

        return [];
    }

    /**
     * @return array<Parameter|Reference>
     */
    protected function parameters(): array
    {
        return Parameters::make($this->route)->build();
    }

    /**
     * @return SpecResponses<string, Response>
     */
    abstract protected function responses(): SpecResponses;

    protected function requestBody(): ?RequestBody
    {
        return null;
    }

    /**
     * @return Collection<string, Example>
     */
    protected function examples(): Collection
    {
        return $this->route->examples();
    }

    /**
     * @param SpecResponses<string, Response> $responses
     */
    protected function addCommonDefaultResponses(SpecResponses $responses): void
    {
        $responses->addResponse('406', $this->builder()->components()->getResponse(DefaultResponse::NotAcceptable_406));
        $responses->addResponse('415', $this->builder()->components()->getResponse(DefaultResponse::UnsupportedMediaType_415));
        $responses->addResponse('422', $this->builder()->components()->getResponse(DefaultResponse::UnprocessableEntity_422));
        if (config('jsonapi-openapi.has_global_rate_limits')) {
            $responses->addResponse('429', $this->builder()->components()->getResponse(DefaultResponse::TooManyRequests_429));
        }
        $responses->addResponse('4XX', $this->builder()->components()->getResponse(DefaultResponse::ServerError_5XX));
        $responses->addResponse('5XX', $this->builder()->components()->getResponse(DefaultResponse::ServerError_5XX));
    }

    protected function toResponseDefaultIfMatching(Response $response, DefaultResponse $default): Reference|Response
    {
        if (
            serialize($response->getSerializableData()) ===
            serialize($this->builder()->components()->getResponse($default, false)->getSerializableData())
        ) {
            return $this->builder()->components()->getResponse($default);
        }

        return $response;
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function responsableToJson(Responsable $responsable): array
    {
        return json_decode($responsable->toResponse(request())->getContent() ?: '{}', true);
    }

    protected function getSelfLink(): Schema
    {
        return new Schema(['type' => 'object',
            'properties' => [
                'self' => Link::makeSelf($this->route->url()),
            ],
        ]);
    }
}
