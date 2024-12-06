<?php

namespace haddowg\JsonApiOpenApi\OpenApi;

use cebe\openapi\spec\Callback;
use cebe\openapi\spec\Components as SpecComponents;
use cebe\openapi\spec\Example;
use cebe\openapi\spec\Header;
use cebe\openapi\spec\Link;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\SecurityScheme;
use haddowg\JsonApiOpenApi\Concerns\PerformsTranslation;
use haddowg\JsonApiOpenApi\Enums\DefaultHeader;
use haddowg\JsonApiOpenApi\Enums\DefaultResponse;
use haddowg\JsonApiOpenApi\Enums\DefaultSchema;
use haddowg\JsonApiOpenApi\JsonApiOpenApi;
use Illuminate\Support\Collection;

class Components
{
    use PerformsTranslation;

    /**
     * @var Collection<string,Schema>
     */
    public readonly Collection $schemas;

    /**
     * @var Collection<string, Response>
     */
    public readonly Collection $responses;

    /**
     * @var Collection<string, Parameter>
     */
    public readonly Collection $parameters;

    /**
     * @var Collection<string, Example>
     */
    public readonly Collection $examples;

    /**
     * @var Collection<string,RequestBody>
     */
    public readonly Collection $requestBodies;

    /**
     * @var Collection<string, Header>
     */
    public readonly Collection $headers;

    /**
     * @var Collection<string, SecurityScheme>
     */
    public readonly Collection $securitySchemes;

    /**
     * @var Collection<string, Link>
     */
    public readonly Collection $links;

    /**
     * @var Collection<string, Callback>
     */
    public readonly Collection $callbacks;

    private string $jsonApiVersion;

    public function __construct()
    {
        $this->schemas = new Collection();
        $this->responses = new Collection();
        $this->parameters = new Collection();
        $this->examples = new Collection();
        $this->requestBodies = new Collection();
        $this->headers = new Collection();
        $this->securitySchemes = new Collection();
        $this->links = new Collection();
        $this->callbacks = new Collection();

        $this->jsonApiVersion = JsonApiOpenApi::VERSION_1_0;
    }

    public static function make(): self
    {
        return new self();
    }

    public function withVersion(string $version): self
    {
        $this->jsonApiVersion = $version;

        return $this;
    }

    public function init(): self
    {
        $this->addParameter('JsonApi:acceptInHeader', new Parameter([
            'name' => 'Accept',
            'in' => 'header',
            'description' => $this->getTranslation('parameters.media-type'),
            'required' => false,
            'schema' => [
                'type' => 'string',
                'const' => 'application/vnd.api+json',
                'default' => 'application/vnd.api+json',
            ],
        ]));

        $this->addParameter('JsonApi:fieldsParameter', new Parameter([
            'name' => 'fields',
            'in' => 'query',
            'description' => $this->getTranslation('parameters.fields'),
            'required' => false,
            'allowEmptyValue' => false,
            'schema' => [
                'type' => 'object',
                'additionalProperties' => [
                    'x-additionalPropertiesName' => 'resource-type',
                    'type' => 'string',
                ],
            ],
            'example' => [
                'resource-type' => 'field,anotherField',
            ],
            'allowReserved' => true,
            'style' => 'deepObject',
        ]));

        $this->addSchema(DefaultSchema::JsonApi, new Schema([
            'description' => $this->getTranslation('schema.jsonapi.description'),
            'type' => 'object',
            'properties' => array_filter([
                'version' => [
                    'description' => $this->getTranslation('schema.jsonapi.properties.version'),
                    'type' => 'string',
                    'const' => $this->jsonApiVersion,
                ],
                'meta' => [
                    '$ref' => '#/components/schemas/JsonApi:meta',
                ],
                'ext' => $this->jsonApiVersion === JsonApiOpenApi::VERSION_1_1 ? [
                    'description' => $this->getTranslation('schema.jsonapi.properties.ext'),
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ] : null,
                'profile' => $this->jsonApiVersion === JsonApiOpenApi::VERSION_1_1 ? [
                    'description' => $this->getTranslation('schema.jsonapi.properties.profile'),
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ] : null,
            ]),
            'additionalProperties' => false,
            'example' => [
                'version' => $this->jsonApiVersion,
            ],
        ]));
        $this->addSchema(DefaultSchema::Links, new Schema([
            'description' => $this->getTranslation('schema.links'),
            'type' => 'object',
            'additionalProperties' => [
                'x-additionalPropertiesName' => 'linkKey',
                'anyOf' => [
                    [
                        'type' => 'string',
                        'format' => 'url',
                    ],
                    [
                        'type' => 'null',
                    ],
                    [
                        '$ref' => '#/components/schemas/JsonApi:linkObject',
                    ],
                ],
            ],
        ]));
        $this->addSchema(DefaultSchema::LinkObject, new Schema([
            'description' => $this->getTranslation('schema.linkObject.description'),
            'type' => 'object',
            'properties' => array_filter([
                'href' => [
                    'description' => $this->getTranslation('schema.linkObject.properties.href'),
                    'type' => 'string',
                    'format' => 'url',
                ],
                'rel' => $this->jsonApiVersion === JsonApiOpenApi::VERSION_1_1 ? [
                    'description' => $this->getTranslation('schema.linkObject.properties.rel'),
                    'type' => 'string',
                ] : null,
                'describedBy' => $this->jsonApiVersion === JsonApiOpenApi::VERSION_1_1 ? [
                    'description' => $this->getTranslation('schema.linkObject.properties.describedBy'),
                    'type' => 'string',
                ] : null,
                'title' => $this->jsonApiVersion === JsonApiOpenApi::VERSION_1_1 ? [
                    'description' => $this->getTranslation('schema.linkObject.properties.title'),
                    'type' => 'string',
                ] : null,
                'type' => $this->jsonApiVersion === JsonApiOpenApi::VERSION_1_1 ? [
                    'description' => $this->getTranslation('schema.linkObject.properties.type'),
                    'type' => 'string',
                ] : null,
                'hreflang' => $this->jsonApiVersion === JsonApiOpenApi::VERSION_1_1 ? [
                    'description' => $this->getTranslation('schema.linkObject.properties.hreflang'),
                    'oneOf' => [
                        ['type' => 'string'],
                        ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                ] : null,
                'meta' => [
                    '$ref' => '#/components/schemas/JsonApi:meta',
                ],
            ]),
            'required' => [
                'href',
            ],
            'additionalProperties' => false,
        ]));
        $this->addSchema(DefaultSchema::Meta, new Schema([
            'description' => $this->getTranslation('schema.meta'),
            'type' => 'object',
            'additionalProperties' => true,
            'x-additionalPropertiesName' => 'metaKey',
        ]));

        $this->addSchema(DefaultSchema::Id, new Schema(array_filter([
            'description' => $this->getTranslation('schema.resourceId'),
            'type' => 'string',
            'format' => config('jsonapi-openapi.resource_id_format'),
        ])));

        $this->addSchema(DefaultSchema::Type, new Schema([
            'description' => $this->getTranslation('schema.resourceType'),
            'type' => 'string',
        ]));

        $this->addSchema(DefaultSchema::Errors, new Schema([
            'description' => $this->getTranslation('schema.errors'),
            'type' => 'array',
            'items' => [
                '$ref' => '#/components/schemas/JsonApi:error',
            ],
        ]));

        $this->addSchema(DefaultSchema::Failure, new Schema([
            'type' => 'object',
            'properties' => [
                'errors' => [
                    '$ref' => '#/components/schemas/JsonApi:errors',
                ],
                'meta' => [
                    '$ref' => '#/components/schemas/JsonApi:meta',
                ],
                'jsonapi' => [
                    '$ref' => '#/components/schemas/JsonApi:jsonapi',
                ],
            ],
            'required' => [
                'errors',
                'jsonapi',
            ],
            'additionalProperties' => false,
        ]));

        $this->addSchema(DefaultSchema::Error, new Schema([
            'description' => $this->getTranslation('schema.error.description'),
            'type' => 'object',
            'minProperties' => 1,
            'properties' => [
                'id' => [
                    'description' => $this->getTranslation('schema.error.properties.id'),
                    'type' => 'string',
                ],
                'code' => [
                    'description' => $this->getTranslation('schema.error.properties.code'),
                    'type' => 'string',
                ],
                'detail' => [
                    'description' => $this->getTranslation('schema.error.properties.detail'),
                    'type' => 'string',
                ],
                'status' => [
                    'description' => $this->getTranslation('schema.error.properties.status'),
                    'type' => 'string',
                ],
                'title' => [
                    'description' => $this->getTranslation('schema.error.properties.title'),
                    'type' => 'string',
                ],
                'source' => [
                    'type' => 'object',
                    'description' => $this->getTranslation('schema.error.properties.source.description'),
                    'properties' => [
                        'pointer' => [
                            'type' => 'string',
                            'description' => $this->getTranslation('schema.error.properties.source.pointer'),
                        ],
                        'parameter' => [
                            'type' => 'string',
                            'description' => $this->getTranslation('schema.error.properties.source.parameter'),
                        ],
                    ],
                ],
            ],
            'additionalProperties' => false,
        ]));

        $this->addResponse(DefaultResponse::Deleting_204, new Response([
            'description' => $this->getTranslation('resource.actions.deleting.responses.204.description'),
            'headers' => $this->getRateLimitHeaders(),
        ]));

        $this->addResponse(DefaultResponse::AttachingRelationship_204, new Response([
            'description' => $this->getTranslation('resource.actions.attachingRelationship.responses.204.description'),
            'headers' => $this->getRateLimitHeaders(),
        ]));

        $this->addResponse(DefaultResponse::DetachingRelationship_204, new Response([
            'description' => $this->getTranslation('resource.actions.detachingRelationship.responses.204.description'),
            'headers' => $this->getRateLimitHeaders(),
        ]));

        $this->addResponse(DefaultResponse::UpdatingRelationship_204, new Response([
            'description' => $this->getTranslation('resource.actions.updatingRelationship.responses.204.description'),
            'headers' => $this->getRateLimitHeaders(),
        ]));

        $this->addResponse(DefaultResponse::Creating_403, new Response([
            'description' => $this->getTranslation('resource.actions.creating.responses.403.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.403.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::Updating_403, new Response([
            'description' => $this->getTranslation('resource.actions.updating.responses.403.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.403.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::AttachingRelationship_403, new Response([
            'description' => $this->getTranslation('resource.actions.attachingRelationship.responses.403.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.403.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::DetachingRelationship_403, new Response([
            'description' => $this->getTranslation('resource.actions.detachingRelationship.responses.403.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.403.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::UpdatingRelationship_403, new Response([
            'description' => $this->getTranslation('resource.actions.updatingRelationship.responses.403.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.403.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::Creating_404, new Response([
            'description' => $this->getTranslation('resource.actions.creating.responses.404.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.404.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::Deleting_404, new Response([
            'description' => $this->getTranslation('resource.actions.deleting.responses.404.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.404.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::Updating_404, new Response([
            'description' => $this->getTranslation('resource.actions.updating.responses.404.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.404.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::ViewingOne_404, new Response([
            'description' => $this->getTranslation('resource.actions.viewingOne.responses.404.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.404.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::ViewingRelated_404, new Response([
            'description' => $this->getTranslation('resource.actions.viewingRelated.responses.404.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.404.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::ViewingRelationship_404, new Response([
            'description' => $this->getTranslation('resource.actions.viewingRelationship.responses.404.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.404.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::NotAcceptable_406, new Response([
            'description' => $this->getTranslation('responses.406.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.406.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::Creating_409, new Response([
            'description' => $this->getTranslation('resource.actions.creating.responses.409.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.409.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::Updating_409, new Response([
            'description' => $this->getTranslation('resource.actions.updating.responses.409.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.409.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::UnsupportedMediaType_415, new Response([
            'description' => $this->getTranslation('responses.415.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.415.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::UnprocessableEntity_422, new Response([
            'description' => $this->getTranslation('responses.422.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.422.example'),
                ],
            ],
        ]));
        $this->addResponse(DefaultResponse::TooManyRequests_429, new Response([
            'description' => $this->getTranslation('responses.429.description'),
            'headers' => [
                ...$this->getRateLimitHeaders(),
                'Retry-After' => [
                    'description' => $this->getTranslation('headers.retryAfter'),
                    'schema' => [
                        'type' => 'integer',
                    ],
                ],
                'X-Rate-Limit-Reset' => [
                    'description' => $this->getTranslation('headers.xRateLimit.reset'),
                    'schema' => [
                        'type' => 'integer',
                    ],
                ],
            ],
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.429.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::BadRequest_4XX, new Response([
            'description' => $this->getTranslation('responses.4XX.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.4XX.example'),
                ],
            ],
        ]));

        $this->addResponse(DefaultResponse::ServerError_5XX, new Response([
            'description' => $this->getTranslation('responses.5XX.description'),
            'headers' => $this->getRateLimitHeaders(),
            'content' => [
                'application/vnd.api+json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/JsonApi:failure',
                    ],
                    'example' => $this->getTranslation('responses.5XX.example'),
                ],
            ],
        ]));

        $this->addHeader(DefaultHeader::XRateLimitLimit->value, new Header([
            'description' => $this->getTranslation('headers.xRateLimit.limit'),
            'schema' => [
                'type' => 'integer',
            ],
            'example' => 60,
        ]));

        $this->addHeader(DefaultHeader::XRateLimitRemaining->value, new Header([
            'description' => $this->getTranslation('headers.xRateLimit.remaining'),
            'schema' => [
                'type' => 'integer',
            ],
            'example' => 59,
        ]));

        return $this;
    }

    public function addSchema(DefaultSchema|string $name, Schema $schema): Reference
    {
        $name = $name instanceof DefaultSchema ? $name->value : $name;

        $this->schemas->put($name, $schema);

        return new Reference(['$ref' => "#/components/schemas/{$name}"]);
    }

    public function hasSchema(DefaultSchema|string $name): bool
    {
        return $this->schemas->has($name instanceof DefaultSchema ? $name->value : $name);
    }

    public function getSchema(DefaultSchema|string $name, bool $asRef = true): Schema|Reference|null
    {
        if (!$this->hasSchema($name)) {
            return null;
        }

        $name = $name instanceof DefaultSchema ? $name->value : $name;

        return $asRef ?
            new Reference(['$ref' => "#/components/schemas/{$name}"]) :
            $this->schemas->get($name);
    }

    public function addResponse(DefaultResponse|string $name, Response $response): Reference
    {
        $name = $name instanceof DefaultResponse ? $name->value : $name;

        $this->responses->put($name, $response);

        return new Reference(['$ref' => "#/components/responses/{$name}"]);
    }

    public function hasResponse(DefaultResponse|string $name): bool
    {
        return $this->responses->has($name instanceof DefaultResponse ? $name->value : $name);
    }

    public function getResponse(DefaultResponse|string $name, bool $asRef = true): Response|Reference|null
    {
        if (!$this->hasResponse($name)) {
            return null;
        }

        $name = $name instanceof DefaultResponse ? $name->value : $name;

        return $asRef ?
            new Reference(['$ref' => "#/components/responses/{$name}"]) :
            $this->responses->get($name);
    }

    public function addParameter(string $name, Parameter $parameter): Reference
    {
        $this->parameters->put($name, $parameter);

        return new Reference(['$ref' => "#/components/parameters/{$name}"]);
    }

    public function hasParameter(string $name): bool
    {
        return $this->parameters->has($name);
    }

    public function getParameter(string $name, bool $asRef = true): Parameter|Reference|null
    {
        if (!$this->hasParameter($name)) {
            return null;
        }

        return $asRef ?
            new Reference(['$ref' => "#/components/parameters/{$name}"]) :
            $this->parameters->get($name);
    }

    public function addExample(string $name, Example $example): Reference
    {
        $this->examples->put($name, $example);

        return new Reference(['$ref' => "#/components/examples/{$name}"]);
    }

    public function addRequestBody(string $name, RequestBody $requestBody): Reference
    {
        $this->requestBodies->put($name, $requestBody);

        return new Reference(['$ref' => "#/components/requestBodies/{$name}"]);
    }

    public function addHeader(string $name, Header $header): Reference
    {
        $this->headers->put($name, $header);

        return new Reference(['$ref' => "#/components/headers/{$name}"]);
    }

    public function getDefaultHeader(DefaultHeader $defaultHeader): Reference
    {
        return new Reference(['$ref' => "#/components/headers/{$defaultHeader->value}"]);
    }

    /**
     * @return array<string, Reference>
     */
    public function getRateLimitHeaders(): array
    {
        if (config('jsonapi-openapi.has_global_rate_limits')) {
            return [
                DefaultHeader::XRateLimitLimit->value => $this->getDefaultHeader(DefaultHeader::XRateLimitLimit),
                DefaultHeader::XRateLimitRemaining->value => $this->getDefaultHeader(DefaultHeader::XRateLimitRemaining),
            ];
        }

        return [];
    }

    public function addSecurityScheme(string $name, SecurityScheme $securityScheme): Reference
    {
        $this->securitySchemes->put($name, $securityScheme);

        return new Reference(['$ref' => "#/components/securitySchemes/{$name}"]);
    }

    public function addLink(string $name, Link $link): Reference
    {
        $this->links->put($name, $link);

        return new Reference(['$ref' => "#/components/links/{$name}"]);
    }

    public function addCallback(string $name, Callback $callback): Reference
    {
        $this->callbacks->put($name, $callback);

        return new Reference(['$ref' => "#/components/callbacks/{$name}"]);
    }

    public function build(): SpecComponents
    {
        return new SpecComponents([
            'schemas' => $this->schemas->sortKeys()->all(),
            'responses' => $this->responses->sortKeys()->all(),
            'parameters' => $this->parameters->sortKeys()->all(),
            'examples' => $this->examples->sortKeys()->all(),
            'requestBodies' => $this->requestBodies->sortKeys()->all(),
            'headers' => $this->headers->sortKeys()->all(),
            'securitySchemes' => $this->securitySchemes->sortKeys()->all(),
            'links' => $this->links->sortKeys()->all(),
            'callbacks' => $this->callbacks->sortKeys()->all(),
        ]);
    }
}
