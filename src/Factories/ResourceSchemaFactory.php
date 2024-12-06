<?php

namespace haddowg\JsonApiOpenApi\Factories;

use Carbon\Exceptions\InvalidFormatException;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use haddowg\JsonApiOpenApi\Attributes\AttributeHelper;
use haddowg\JsonApiOpenApi\Concerns\InfersSchema;
use haddowg\JsonApiOpenApi\Concerns\PerformsTranslationForRoute;
use haddowg\JsonApiOpenApi\Descriptors\Id;
use haddowg\JsonApiOpenApi\Descriptors\Link;
use haddowg\JsonApiOpenApi\Descriptors\Route;
use haddowg\JsonApiOpenApi\Descriptors\UnConditionalIterator;
use haddowg\JsonApiOpenApi\Descriptors\Validation\ValidationRules;
use haddowg\JsonApiOpenApi\Enums\DefaultSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use LaravelJsonApi\Contracts\Resources\Serializer\Attribute as SerializableAttribute;
use LaravelJsonApi\Contracts\Resources\Serializer\Relation as SerializableRelation;
use LaravelJsonApi\Contracts\Schema\Field;
use LaravelJsonApi\Contracts\Schema\Relation;
use LaravelJsonApi\Contracts\Schema\Schema as JsonApiSchema;
use LaravelJsonApi\Core\Resources\JsonApiResource;
use LaravelJsonApi\Core\Resources\Relation as ResourceRelation;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\Attribute;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\Map;
use LaravelJsonApi\Eloquent\Fields\Number;

class ResourceSchemaFactory
{
    use InfersSchema;
    use PerformsTranslationForRoute;

    public const RESOURCE_SCHEMA_TAG = 'jsonapi-resource';

    public function __construct(
        protected Route $route,
    ) {}

    public static function make(Route $route): self
    {
        return new self($route);
    }

    public function storeResourceSchema(string $resourceType): Schema
    {
        return new Schema([
            'title' => $this->getTranslationForResource('data.store', $resourceType),
            'type' => 'object',
            'properties' => [
                'data' => $this->storeResourceDataSchema($resourceType),
            ],
            'additionalProperties' => false,
            'required' => ['data'],
        ]);
    }

    public function updateResourceSchema(string $resourceType): Schema
    {
        return new Schema([
            'title' => $this->getTranslationForResource('data.update', $resourceType),
            'type' => 'object',
            'properties' => [
                'data' => $this->updateResourceDataSchema($resourceType),
            ],
            'additionalProperties' => false,
            'required' => ['data'],
        ]);
    }

    public function oneResourceSchema(string $resourceType): Schema
    {
        return new Schema([
            'title' => $this->getTranslationForResource('data.one', $resourceType),
            'type' => 'object',
            'properties' => array_filter([
                'data' => $this->resourceSchema($resourceType),
                'included' => $this->included(),
                'links' => $this->resourceLinks($resourceType),
                'meta' => $this->builder()->components()->getSchema(DefaultSchema::Meta),
                'jsonapi' => $this->builder()->components()->getSchema(DefaultSchema::JsonApi),
            ]),
        ]);
    }

    public function manyResourceSchema(string $resourceType): Schema
    {
        $paginator = $this->route->paginator();

        return new Schema([
            'title' => $this->getTranslationForResource('data.many', $resourceType),
            'type' => 'object',
            'properties' => array_filter([
                'data' => [
                    'type' => 'array',
                    'items' => $this->resourceSchema($resourceType),
                    'uniqueItems' => true,
                ],
                'included' => $this->included(),
                'links' => [
                    'allOf' => [
                        $paginator ? $paginator->links() : $this->builder()->components()->getSchema(DefaultSchema::Links),
                        new Schema([
                            'type' => 'object',
                            'properties' => [
                                'self' => Link::makeSelf($this->route->url()),
                            ],
                        ]),
                    ],
                ],
                'meta' => $paginator ? $paginator->meta() : $this->builder()->components()->getSchema(DefaultSchema::Meta),
                'jsonapi' => $this->builder()->components()->getSchema(DefaultSchema::JsonApi),
            ]),
        ]);
    }

    public function resourceSchema(string $resourceType): Reference
    {
        $name = Str::ucfirst(Str::camel(Str::singular($resourceType)));
        /** @var Reference|null $existingSchema */
        $existingSchema = $this->builder()->components()->getSchema($name);
        if ($existingSchema) {
            return $existingSchema;
        }

        $schema = $this->builder()->server()->schemas()->schemaFor($resourceType);
        $resource = $this->route->resources()->single($resourceType);
        if (!$resource) {
            $this->builder()->output()?->writeln(sprintf('<comment>    - Unable to generate example resource for the `%s` resource type. Attributes may be missing or inaccurate in the generated resource schema. </comment>', $resourceType));
        }

        $resourceSchemaTag = $this->builder()->tags()->add(self::RESOURCE_SCHEMA_TAG);
        $resourceTag = $this->builder()->tags()->addResourceType($resourceType);

        $relationships = $this->getResourceRelationships($schema, $resource);
        $attributes = $this->getResourceAttributes($schema, $resource);
        $required = ['id', 'type'];

        if ($attributes) {
            $required[] = 'attributes';
        }
        if ($relationships) {
            $required[] = 'relationships';
        }

        $schema = new Schema([
            'x-tags' => [
                $resourceTag,
                $resourceSchemaTag,
            ],
            'title' => $this->getTranslationForResource('schema.title', $resourceType),
            'type' => 'object',
            'properties' => array_filter([
                'id' => Id::make($this->builder(), $schema->id()),
                'type' => $this->getTypeSchema($resourceType),
                'attributes' => $attributes,
                'relationships' => $relationships,
                'links' => $this->resourceLinks($resourceType),
                'meta' => $this->resourceMeta($resourceType),
            ]),
            'required' => $required,
        ]);

        return $this->builder()->components()->addSchema($name, $schema);
    }

    public function getResourceLinkageSchema(string $resourceType): Reference
    {
        $name = Str::ucfirst(Str::camel(Str::singular($resourceType))) . '_Linkage';
        /** @var Reference|null $existingSchema */
        $existingSchema = $this->builder()->components()->getSchema($name);
        if ($existingSchema) {
            return $existingSchema;
        }

        $schema = $this->builder()->server()->schemas()->schemaFor($resourceType);

        return $this->builder()->components()->addSchema($name, new Schema([
            'type' => 'object',
            'properties' => [
                'id' => Id::make($this->builder(), $schema->id()),
                'type' => $this->getTypeSchema($resourceType),
                // TODO: get resource identifierMeta https://laraveljsonapi.io/4.x/resources/meta.html#identifier-meta
                'meta' => $this->builder()->components()->getSchema(DefaultSchema::Meta),
            ],
            'required' => [
                'id',
                'type',
            ],
            'additionalProperties' => false,
        ]));
    }

    public function getResourceRelationshipData(Relation $relation, string $resourceType, bool $nullable = true): Schema|Reference
    {
        return match ($relation->toOne()) {
            true => $nullable ? new Schema([
                'oneOf' => [
                    ['type' => 'null'],
                    $this->getResourceLinkageSchema($relation->inverse()),
                ],
            ]) : $this->getResourceLinkageSchema($relation->inverse()),
            default => new Schema([
                'type' => 'array',
                'items' => $this->getResourceLinkageSchema($relation->inverse()),
                'uniqueItems' => true,
            ]),
        };
    }

    public function included(): ?Schema
    {
        $includes = $this->route->validationRules()->includes();

        if ($includes === null) {
            return null;
        }

        $schema = $this->route->schema();

        if ($includes->isEmpty()) {
            /** @var iterable<int,string> $includePaths */
            $includePaths = $schema->includePaths();
            $includes = collect($includePaths);
            if ($includes->isEmpty()) {
                return null;
            }
        }

        $includeResourceTypes = Collection::empty();
        foreach ($includes as $includePath) {
            $paths = explode('.', $includePath);
            $resourceSchema = $schema;
            while ($relation = array_shift($paths)) {
                $includeResourceTypes->push(...$resourceSchema->relationship($relation)->allInverse());
                $resourceSchema = $this->builder()->server()->schemas()->schemaFor($resourceSchema->relationship($relation)->inverse());
            }
        }
        $includeResourceTypes = $includeResourceTypes->unique()->sort()->values();
        if ($includeResourceTypes->isEmpty()) {
            return null;
        }
        $includedResourceSchemas = $includeResourceTypes->map(fn ($resourceType) => $this->resourceSchema($resourceType));

        return new Schema([
            'type' => 'array',
            'items' => $includedResourceSchemas->count() > 1 ?
                [
                    'oneOf' => $includedResourceSchemas->all(),
                ] :
                $includedResourceSchemas->first(),
            'description' => $this->getTranslationForResource('schema.included', default: $this->getTranslation('schema.included')),
        ]);
    }

    protected function getTypeSchema(string $resourceType): Schema
    {
        /** @var Schema $type */
        $type = clone $this->builder()->components()->getSchema(DefaultSchema::Type->value, false);
        // @phpstan-ignore-next-line const not recognized on Schema but works
        $type->const = $resourceType;

        return $type;
    }

    /**
     * @return Collection<string, Schema>
     */
    protected function getAttributeSchemasFromAttributes(JsonApiSchema $schema, ?JsonApiResource $resource): Collection
    {
        $schemas = Collection::empty();
        if ($resource) {
            if ($attributes = AttributeHelper::getAttributes($resource, \haddowg\JsonApiOpenApi\Attributes\Attribute::class)) {
                foreach ($attributes as $attribute) {
                    if ($attribute->name()) {
                        $schemas->put($attribute->key(), $this->builder()->components()->addSchema($attribute->name(), $attribute->schema()));
                        continue;
                    }
                    $schemas->put($attribute->key(), $attribute->schema());
                }
            }
            if ($attributes = AttributeHelper::getAttributes($resource, \haddowg\JsonApiOpenApi\Attributes\Attribute::class, 'attributes')) {
                foreach ($attributes as $attribute) {
                    if ($attribute->name()) {
                        $schemas->put($attribute->key(), $this->builder()->components()->addSchema($attribute->name(), $attribute->schema()));
                        continue;
                    }
                    $schemas->put($attribute->key(), $attribute->schema());
                }
            }
        }
        if ($attributes = AttributeHelper::getAttributes($schema, \haddowg\JsonApiOpenApi\Attributes\Attribute::class)) {
            foreach ($attributes as $attribute) {
                if ($attribute->name()) {
                    $schemas->put($attribute->key(), $this->builder()->components()->addSchema($attribute->name(), $attribute->schema()));
                    continue;
                }
                $schemas->put($attribute->key(), $attribute->schema());
            }
        }

        if ($attributes = AttributeHelper::getAttributes($schema, \haddowg\JsonApiOpenApi\Attributes\Attribute::class, 'fields')) {
            foreach ($attributes as $attribute) {
                if ($attribute->name()) {
                    $schemas->put($attribute->key(), $this->builder()->components()->addSchema($attribute->name(), $attribute->schema()));
                    continue;
                }
                $schemas->put($attribute->key(), $attribute->schema());
            }
        }

        return $schemas;
    }

    protected function getResourceAttributes(JsonApiSchema $schema, ?JsonApiResource $resource): ?Schema
    {
        $resourceAttributes = new UnConditionalIterator($resource?->attributes(request()) ?? []);

        /** @var iterable<int, \LaravelJsonApi\Contracts\Schema\Attribute> $attributeFields */
        $attributeFields = $schema->attributes();
        $fields = collect($attributeFields)->mapWithKeys(fn ($field) => [$field instanceof SerializableAttribute ? $field->serializedFieldName() : $field->name() => $field]);
        $attributeSchemas = $this->getAttributeSchemasFromAttributes($schema, $resource);
        /** @var array<string, Schema> $attributes */
        $attributes = [];
        foreach ($resourceAttributes as $key => $value) {
            if ($attributeSchemas->has($key)) {
                $attributes[$key] = $attributeSchemas->get($key);
                continue;
            }

            // if the resource attribute has an Attribute definition in the Schema infer the field definition and example value(s)
            if ($field = $fields->get($key)) {
                $attributes[$key] = $this->getFieldSchema($field, $value);
                continue;
            }
            // else infer type from the example value(s)
            $attributes[$key] = $this->inferSchemaFromValue($value);
        }

        foreach ($attributes as $key => $attribute) {
            $this->describeAttribute($schema::type(), $key, $attribute);
        }

        if (empty($attributes)) {
            return null;
        }

        return new Schema([
            'description' => $this->getTranslationForResource('schema.attributes-description', $schema::type()),
            'type' => 'object',
            'properties' => $attributes,
            'additionalProperties' => false,
        ]);
    }

    protected function describeAttribute(string $resourceType, string $key, Schema|Reference $schema): void
    {
        if ($schema instanceof Reference) {
            return;
        }

        if (empty($schema->description)) {
            $descriptionKey = match ($schema->type) {
                'object', 'array' => "schema.attributes.{$key}.description",
                default => "schema.attribute.{$key}",
            };
            $description = $this->getTranslationForResource($descriptionKey, $resourceType, [
                'attribute' => $key,
            ], default: Str::contains($key, '.') ? null : $this->getTranslationForResource('schema.attribute', $resourceType, [
                'attribute' => $key,
            ]));
            if ($description) {
                $schema->description = $description;
            }
        }

        if ($schema->type === 'object' && !empty($schema->properties)) {
            foreach ($schema->properties as $subKey => $property) {
                $this->describeAttribute($resourceType, "{$key}.properties.{$subKey}", $property);
            }
        }
        if ($schema->type === 'array' && $schema->items) {
            $this->describeAttribute($resourceType, "{$key}.items", $schema->items);
        }
    }

    protected function getFieldSchema(Field $field, mixed $value): Schema|Reference
    {
        return match (true) {
            $field instanceof Map && (is_array($value) || is_null($value)) => $this->mapToObjectSchema($field, $value),
            $field instanceof DateTime && (is_string($value) || $value instanceof \DateTime || empty($value)) => $this->toDateTimeSchema($field, $value),
            $field instanceof ArrayList && is_array($value) => $this->toArraySchema($value),
            $field instanceof ArrayHash && is_array($value) => $this->toObjectSchema($value),
            $field instanceof Number && is_numeric($value) => new Schema([
                'type' => 'number',
                'example' => $value + 0,
            ]),
            $field instanceof Boolean && is_bool($value) => new Schema([
                'type' => 'boolean',
                'example' => $value,
            ]),
            default => $this->inferSchemaFromValue($value),
        };
    }

    /**
     * @param array<string,mixed>|null $value
     */
    protected function mapToObjectSchema(Map $map, ?array $value): Schema
    {
        /**
         * @var Collection<int, Attribute> $fields
         */
        $fields = collect(invade($map)->map);

        return new Schema(array_filter([
            'type' => 'object',
            'properties' => $fields->mapWithKeys(
                fn ($field) => [
                    $field->serializedFieldName() => $this->getFieldSchema($field, $value ? ($value[$field->name()] ?? null) : null),
                ],
            )->toArray(),
            'example' => empty($value) ? null : $value,
        ]));
    }

    protected function toDateTimeSchema(DateTime $field, ?string $value): Schema
    {
        $schema = new Schema([
            'type' => 'string',
            'format' => 'date-time',
        ]);

        if (empty($value)) {
            $schema->example = Date::now()->toIso8601String();

            return $schema;
        }
        try {
            Carbon::createFromFormat('Y-m-d', $value);
            $schema->format = 'date';
            $schema->example = $value;

            return $schema;
        } catch (InvalidFormatException $ex) {
            // ignore
        }

        $schema->example = $value;

        return $schema;
    }

    protected function getResourceRelationships(JsonApiSchema $schema, ?JsonApiResource $resource): ?Schema
    {
        /** @var iterable<string, ResourceRelation> $resourceRelationships */
        $resourceRelationships = new UnConditionalIterator($resource?->relationships(null) ?? []);

        /**
         * @var iterable<int, Relation> $relationFields
         */
        $relationFields = $schema->relationships();
        $relations = collect($relationFields)->mapWithKeys(function (Relation $relation) {
            if ($relation instanceof SerializableRelation) {
                return [$relation->serializedFieldName() => $relation];
            }

            return [$relation->name() => $relation];
        });

        $relationships = [];
        foreach ($resourceRelationships as $key => $value) {
            if ($relation = $relations->get($key)) {
                $relationships[$relation->name()] = $this->getResourceRelationship($relation, $value, $schema::type());
            }
            // TODO relation on resource without schema relationship? throw?
        }
        if (empty($relationships)) {
            return null;
        }

        return new Schema([
            'description' => $this->getTranslationForResource('schema.relationships-description', $schema::type()),
            'type' => 'object',
            'properties' => $relationships,
            'additionalProperties' => false,
        ]);
    }

    protected function getResourceRelationship(Relation $relation, ResourceRelation $resourceRelation, string $resourceType): Schema
    {
        $key = $relation->toOne() ? 'toOne' : 'toMany';
        $replacements = $this->getResourceRelationshipTranslationReplacements($relation, $resourceType);
        $relationDescription = $this->getTranslationForResource("schema.relationships.{$relation->name()}.description", $resourceType, $replacements);
        if (empty($relationDescription)) {
            $relationDescription = $this->getTranslationForResource("schema.relationship.description.{$key}", $resourceType, $replacements);
        }

        return new Schema(array_filter([
            'type' => 'object',
            'description' => !empty($relationDescription) ? $relationDescription : null,
            'properties' => [
                'links' => $this->getResourceRelationshipLinks($relation, $resourceRelation, $resourceType),
                'data' => $this->getResourceRelationshipData($relation, $resourceType),
                // TODO: get meta from resourceRelation
                'meta' => $this->builder()->components()->getSchema(DefaultSchema::Meta),
            ],
        ]));
    }

    protected function getResourceRelationshipLinks(Relation $relation, ResourceRelation $resourceRelation, string $resourceType): Schema
    {
        $resourceLinks = $resourceRelation->links();

        $replacements = $this->getResourceRelationshipTranslationReplacements($relation, $resourceType);
        $links = [];
        foreach ($resourceLinks->all() as $key => $link) {
            $links[$key] = Link::make($link);
        }

        foreach ($links as $key => $linkSchema) {
            $linkDescription = $this->getTranslationForResource("schema.relationships.{$relation->name()}.links.{$key}", $resourceType, [...$replacements, 'link' => $key]);
            if (!empty($linkDescription)) {
                $linkSchema->description = $linkDescription;
                continue;
            }
            if ($key === 'related') {
                $key = $relation->toOne() ? "{$key}.toOne" : "{$key}.toMany";
            }
            $linkDescription = $this->getTranslationForResource("schema.relationship.links.{$key}", $resourceType, [...$replacements, 'link' => $key]);
            if (!empty($linkDescription)) {
                $linkSchema->description = $linkDescription;
            }
        }

        $description = $this->getTranslationForResource("schema.relationships.{$relation->name()}.links-description", $resourceType, $replacements);

        if (empty($description)) {
            $description = $this->getTranslationForResource('schema.relationship.links-description', $resourceType, $replacements);
        }

        return new Schema([
            'description' => $description,
            'type' => 'object',
            'properties' => $links,
        ]);
    }

    protected function resourceLinks(string $resourceType): Schema|Reference
    {
        $name = Str::ucfirst(Str::camel(Str::singular($resourceType))) . '_Links';

        if ($this->builder()->components()->hasSchema($name)) {
            return $this->builder()->components()->getSchema($name);
        }

        $resource = $this->route->resources()->single($resourceType);
        if (!$resource) {
            return $this->builder()->components()->getSchema(DefaultSchema::Links);
        }

        $resourceLinks = $resource->links(request());

        $links = [];
        foreach ($resourceLinks->all() as $key => $link) {
            $links[$key] = Link::make($link);
        }

        foreach ($links as $key => $linkSchema) {
            $linkDescription = $this->getTranslationForResource("schema.links.{$key}", $resourceType, ['link' => $key]);
            if (!empty($linkDescription)) {
                $linkSchema->description = $linkDescription;
                continue;
            }

            if ($key === 'self') {
                $linkDescription = $this->getTranslationForResource('actions.viewingOne.description', $resourceType, ['link' => $key]);
                if (!empty($linkDescription)) {
                    $linkSchema->description = $linkDescription;
                }
            }
        }

        return $this->builder()->components()->addSchema($name, new Schema([
            'allOf' => [
                $this->builder()->components()->getSchema(DefaultSchema::Links),
                new Schema([
                    'type' => 'object',
                    'properties' => $links,
                ]),
            ],
        ]));
    }

    protected function resourceMeta(string $resourceType): Schema|Reference
    {
        $resource = $this->route->resources()->single($resourceType);
        if (!$resource) {
            return $this->builder()->components()->getSchema(DefaultSchema::Meta);
        }

        $meta = (new UnConditionalIterator($resource->meta(request())))->all();
        if (empty($meta)) {
            return $this->builder()->components()->getSchema(DefaultSchema::Meta);
        }

        $schema = $this->toObjectSchema($meta, true);
        $schema->description = $this->getTranslationForResource('schema.meta-description', $resourceType);
        // TODO: recursively translate description for each meta attribute

        return $schema;
    }

    protected function storeResourceDataSchema(string $resourceType): Schema|Reference
    {
        $name = Str::ucfirst(Str::camel(Str::singular($resourceType))) . '_' . Str::ucfirst(Str::camel($this->route->getActionMethod()));
        $schema = $this->builder()->server()->schemas()->schemaFor($resourceType);

        $relationships = $this->getResourceRelationshipsForRequest($schema);

        $required = ['type', 'attributes'];

        if ($schema->id()->acceptsClientIds() && ValidationRules::make($this->route)->isRequired('id')) {
            $required[] = ['id'];
        }
        if ($relationships) {
            $required[] = 'relationships';
        }

        $schema = new Schema([
            'title' => $this->getTranslationForResource('data.store', $resourceType),
            'type' => 'object',
            'properties' => array_filter([
                'id' => $schema->id()->acceptsClientIds() ? Id::make($this->builder(), $schema->id()) : null,
                'type' => $this->getTypeSchema($resourceType),
                'attributes' => $this->getResourceRequestAttributes($schema, true),
                'relationships' => $relationships,
            ]),
            'required' => $required,
            'additionalProperties' => false,
        ]);

        return $this->builder()->components()->addSchema($name, $schema);
    }

    protected function updateResourceDataSchema(string $resourceType): Reference
    {
        $name = Str::ucfirst(Str::camel(Str::singular($resourceType))) . '_' . Str::ucfirst(Str::camel($this->route->getActionMethod()));

        $schema = $this->builder()->server()->schemas()->schemaFor($resourceType);

        $relationships = $this->getResourceRelationshipsForRequest($schema);

        $required = ['id', 'type', 'attributes'];

        if ($relationships) {
            $required[] = 'relationships';
        }

        $schema = new Schema([
            'title' => $this->getTranslationForResource('data.update', $resourceType),
            'type' => 'object',
            'properties' => array_filter([
                'id' => Id::make($this->builder(), $schema->id()),
                'type' => $this->getTypeSchema($resourceType),
                'attributes' => $this->getResourceRequestAttributes($schema),
                'relationships' => $relationships,
            ]),
            'required' => $required,
            'additionalProperties' => false,
        ]);

        return $this->builder()->components()->addSchema($name, $schema);
    }

    protected function getResourceRelationshipsForRequest(JsonApiSchema $schema): ?Schema
    {
        $rules = ValidationRules::make($this->route);
        /**
         * @var iterable<int, Relation> $relationFields
         */
        $relationFields = $schema->relationships();
        $relations = collect($relationFields)->mapWithKeys(function (Relation $relation) {
            if ($relation instanceof SerializableRelation) {
                return [$relation->serializedFieldName() => $relation];
            }

            return [$relation->name() => $relation];
        });

        $relationships = [];
        $required = [];
        foreach ($relations as $key => $relation) {
            if (method_exists($relation, 'isReadOnly') && !$relation->isReadOnly($this->route->request())) {
                if ($rules->isRequired($key)) {
                    $required[] = $key;
                }
                $nullable = $rules->isNullable($key);
                $relationships[$key] = new Schema(['type' => 'object', 'properties' => ['data' => $this->getResourceRelationshipData($relation, $schema::type(), $nullable)], 'required' => ['data']]);
            }
        }

        if (empty($relationships)) {
            return null;
        }

        return new Schema(array_filter([
            'description' => $this->getTranslationForResource('schema.relationships-description', $schema::type()),
            'type' => 'object',
            'properties' => $relationships,
            'required' => empty($required) ? null : $required,
        ]));
    }

    protected function getResourceRequestAttributes(JsonApiSchema $schema, bool $withRequired = false): Schema
    {
        /** @var iterable<int, \LaravelJsonApi\Contracts\Schema\Attribute> $attributeFields */
        $attributeFields = $schema->attributes();
        $fields = collect($attributeFields)->mapWithKeys(fn ($field) => [$field instanceof SerializableAttribute ? $field->serializedFieldName() : $field->name() => $field]);

        $rules = ValidationRules::make($this->route);

        /** @var array<Schema> $attributes */
        $attributes = [];
        $required = [];
        foreach ($fields as $key => $field) {
            if (method_exists($field, 'isReadOnly') && $field->isReadOnly($this->route->request())) {
                continue;
            }

            if (!$rules->has($key)) {
                continue;
            }

            $attributeSchema = $this->inferSchemaFromValidationRules($key, $rules->all());
            if ($rules->isRequired($key)) {
                $required[] = $key;
            }
            $this->describeAttribute($schema::type(), $key, $attributeSchema);
            $attributes[$key] = $attributeSchema;
        }

        $schema = new Schema([
            'description' => $this->getTranslationForResource('schema.attributes-description', $schema::type()),
            'type' => 'object',
            'properties' => $attributes,
            'additionalProperties' => false,
        ]);

        if ($withRequired && !empty($required)) {
            $schema->required = $required;
        }

        return $schema;
    }
}
