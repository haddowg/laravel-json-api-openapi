<?php

namespace haddowg\JsonApiOpenApi\Descriptors;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use haddowg\JsonApiOpenApi\Attributes\AttributeHelper;
use haddowg\JsonApiOpenApi\Attributes\Filter as FilterAttribute;
use haddowg\JsonApiOpenApi\Attributes\Name;
use haddowg\JsonApiOpenApi\Concerns\InfersSchema;
use haddowg\JsonApiOpenApi\Concerns\PerformsTranslationForRoute;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Collection;
use LaravelJsonApi\Contracts\Schema\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\DeserializesValue;

readonly class Filters
{
    use InfersSchema;
    use PerformsTranslationForRoute;

    /**
     * @var Collection<string, array{schema: Schema, name: ?string}>
     */
    protected Collection $filterAttributes;

    public function __construct(protected Route $route)
    {
        $filterAttributes = new Collection();

        $schema = $this->route->schema();
        if (!$schema) {
            $this->filterAttributes = $filterAttributes;

            return;
        }

        $attributes = AttributeHelper::getAttributes($schema, FilterAttribute::class);
        if ($attributes) {
            $filterAttributes = $filterAttributes->merge(collect($attributes)->mapWithKeys(function (FilterAttribute $filter) {
                return [$filter->key() => ['schema' => $filter->schema(), 'name' => $filter->name()]];
            }));
        }

        $attributes = AttributeHelper::getAttributes($schema, FilterAttribute::class, 'filters');
        if ($attributes) {
            $filterAttributes = $filterAttributes->merge(collect($attributes)->mapWithKeys(function (FilterAttribute $filter) {
                return [$filter->key() => ['schema' => $filter->schema(), 'name' => $filter->name()]];
            }));
        }

        $this->filterAttributes = $filterAttributes;
    }

    public static function make(Route $route): self
    {
        return new self($route);
    }

    /**
     * @return array<Parameter|Reference>
     */
    public function build(): array
    {
        if (!$this->route->isFetchingMany() && !$this->route->isFetchingOne()) {
            return [];
        }

        $schema = $this->route->schema();
        if (!$schema) {
            return [];
        }

        $allowedFilters = $this->route->validationRules()->filters();

        if ($allowedFilters === null) {
            return [];
        }

        $filters = collect(iterator_to_array($schema->filters()));

        if ($this->route->hasRelation()) {
            $filters = $filters->concat($this->route->relation()->filters());
        }

        return $filters
            ->when(
                // filter to only the filters that are allowed
                $allowedFilters->isNotEmpty(),
                fn ($filters) => $filters->filter(fn ($filter) => $allowedFilters->has($filter->key())),
            )->map(function (Filter $filter) use ($allowedFilters) {
                // generate the parameter for the filter
                return $this->getParameterForFilter($filter, $allowedFilters->all());
            })->values()->all();
    }

    /**
     * @param array<string, array<string|Rule|ValidationRule>> $rules
     */
    protected function getParameterForFilter(Filter $filter, array $rules): Parameter|Reference
    {
        if ($filterAttribute = AttributeHelper::getAttribute($filter, FilterAttribute::class)) {
            $this->filterAttributes->put($filterAttribute->key(), ['schema' => $filterAttribute->schema(), 'name' => $filterAttribute->name()]);
        }

        $name = $this->getNameForParameter($filter);

        if ($name && $this->builder()->components()->hasParameter($name)) {
            return $this->builder()->components()->getParameter($name);
        }

        $resource = $this->route->schema();
        assert($resource !== null);

        $schema = $this->getSchemaForFilter($filter, $rules);
        $param = new Parameter([
            'name' => "filter[{$filter->key()}]",
            'in' => 'query',
            'required' => false,
            'schema' => $schema,
            'description' => $this->getTranslationForResource("filters.{$filter->key()}", replacements: [
                'filter' => $filter->key(),
            ], default: $this->getTranslationForResource('parameters.filter')),
        ]);

        if ($schema instanceof Schema && $schema->type === 'array') {
            $param->explode = false;
            $param->style = 'form';
        }

        if ($name) {
            return $this->builder()->components()->addParameter($name, $param);
        }

        return $param;
    }

    /**
     * @param array<string, array<string|Rule|ValidationRule>> $rules
     */
    protected function getSchemaForFilter(Filter $filter, array $rules): Schema|Reference
    {
        $schema = new Schema([]);

        // check if there is a Filter attribute on the resource's schema
        if ($this->filterAttributes->has($filter->key())) {
            return $this->filterAttributes->get($filter->key())['schema'];
        }

        // check if the filter class has a Filter attribute
        if ($filterAttribute = AttributeHelper::getAttribute($filter, FilterAttribute::class)) {
            $this->filterAttributes->put($filterAttribute->key(), ['schema' => $filterAttribute->schema(), 'name' => $filterAttribute->name()]);

            return $filterAttribute->schema();
        }

        $isEloquent = $filter instanceof \LaravelJsonApi\Eloquent\Contracts\Filter;

        // check if the filter is using `asBoolean`
        $isBool = $isEloquent
            && in_array(DeserializesValue::class, class_uses_recursive($filter))
            && method_exists($filter, 'deserialize')
            && invade($filter)->deserialize('true') === true;

        $schema->type = $isBool ? 'boolean' : 'string';

        if (count($rules) === 0) {
            return $schema;
        }

        return $this->inferSchemaFromValidationRules($filter->key(), $rules, $schema);
    }

    protected function getNameForParameter(Filter $filter): ?string
    {
        if ($this->filterAttributes->has($filter->key()) && $this->filterAttributes->get($filter->key())['name'] !== null) {
            return $this->filterAttributes->get($filter->key())['name'];
        }

        if ($name = AttributeHelper::getAttribute($filter, Name::class)) {
            return $name->value();
        }

        return null;
    }
}
