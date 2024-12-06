<?php

namespace haddowg\JsonApiOpenApi\Concerns;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use haddowg\JsonApiOpenApi\Descriptors\Enum;
use haddowg\JsonApiOpenApi\Descriptors\Route;
use haddowg\JsonApiOpenApi\Descriptors\Validation\ValidationRules;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use LaravelJsonApi\Validation\Rules\DateTimeIso8601;
use LaravelJsonApi\Validation\Rules\JsonBoolean;
use LaravelJsonApi\Validation\Rules\JsonNumber;

/**
 * @property Route $route
 */
trait InfersSchema
{
    protected function toArraySchema(mixed $arrayOrItem): Schema
    {
        $array = collect(Arr::wrap($arrayOrItem));

        return new Schema([
            'type' => 'array',
            'items' => $this->inferSchemaFromValue($array->first()),
            'example' => $array->toArray(),
        ]);
    }

    protected function toObjectSchema(mixed $data, bool $additionalProperties = false): Schema
    {
        /** @var Collection<int|string, mixed> $data */
        $collected = collect($data);

        return new Schema([
            'type' => 'object',
            'properties' => $collected->mapWithKeys(fn ($value, $key) => [$key => $this->inferSchemaFromValue($value)])->all(),
            'example' => $collected->toArray(),
            'additionalProperties' => $additionalProperties,
        ]);
    }

    protected function inferSchemaFromValue(mixed $value): Schema|Reference
    {
        if ($value instanceof \BackedEnum) {
            return Enum::make(get_class($value))->build();
        }

        $type = gettype($value);

        if ($type === 'array') {
            $type = array_is_list($value) ? 'array' : 'object';
        }

        return match ($type) {
            'integer','double' => new Schema([
                'type' => 'number',
                'example' => $value,
            ]),
            'boolean' => new Schema([
                'type' => 'boolean',
                'example' => $value,
            ]),
            'array' => $this->toArraySchema($value),
            'object' => $this->toObjectSchema($value),
            'NULL' => new Schema(['type' => ['string', 'null']]),
            'string' => new Schema([
                'type' => 'string',
                'example' => $value,
            ]),
            default => new Schema(['type' => 'string']),
        };
    }

    /**
     * @param array<string, string|Rule|ValidationRule|array<string|Rule|ValidationRule>> $rules
     */
    protected function inferSchemaFromValidationRules(string $key, array $rules, Schema|Reference|null $schemaIn = null): Schema|Reference
    {
        $schema = $schemaIn instanceof Schema ? $schemaIn : new Schema([
            'type' => 'string',
        ]);

        $rules = collect(ValidationRules::normalise($rules));
        $rulesForKey = collect($rules->get($key));
        foreach ($rulesForKey as $rule) {
            /** @var Schema $schema */
            switch (true) {
                case $rule === 'boolean' || $rule === 'accepted' || $rule === 'declined' || $rule instanceof JsonBoolean:
                    $schema = $this->setSchemaType($schema, 'boolean');
                    break;
                case $rule === 'string':
                    $schema = $this->setSchemaType($schema, 'string');
                    break;
                case $rule === 'nullable':
                    $schema = $this->makeNullable($schema);
                    break;
                case $rule === 'uuid':
                    $schema = $this->setSchemaType($schema, 'string');
                    $schema->format = 'uuid';
                    break;
                case $rule === 'numeric' || $rule instanceof JsonNumber:
                    $schema = $this->setSchemaType($schema, 'number');
                    break;
                case $rule === 'integer':
                    $schema = $this->setSchemaType($schema, 'integer');
                    break;
                case $rule instanceof DateTimeIso8601:
                    $schema = $this->setSchemaType($schema, 'string');
                    $schema->format = 'date-time';
                    break;
                case $rule === 'array':
                    $propertyRules = $rules->filter(fn ($fieldRules, $field) => Str::startsWith($field, "{$key}."));
                    $isObject = $propertyRules->keys()->doesntContain(fn ($field) => Str::contains($field, '.*'));

                    $schema = $this->setSchemaType($schema, $isObject ? 'object' : 'array');

                    if ($isObject) {
                        $properties = $propertyRules->filter(fn ($fieldRules, $field) => !Str::of($field)->after("{$key}.")->contains('.'));

                        $schema->properties = $properties->mapWithKeys(fn ($fieldRules, $field) => [Str::after($field, "{$key}.") => $this->inferSchemaFromValidationRules($field, $rules->all())])->all();
                        $schema->required = $properties->map(fn ($fieldRules, $field) => collect($fieldRules)->contains('required') || collect($fieldRules)->contains("required_with:{$key}") ? Str::after($field, "{$key}.") : null)->filter()->values()->all();
                        if (empty($schema->required)) {
                            unset($schema->required);
                        }
                        $schema->additionalProperties = false;
                        break;
                    }
                    if ($rules->has("{$key}.*") && collect($rules->get("{$key}.*"))->doesntContain('array')) {
                        $schema->items = $this->inferSchemaFromValidationRules("{$key}.*", $rules->all());
                        break;
                    }
                    $properties = $propertyRules->filter(fn ($fieldRules, $field) => Str::startsWith($field, "{$key}.*."))->filter(fn ($fieldRules, $field) => !Str::of($field)->after("{$key}.*.")->contains('.'));

                    $itemSchema = new Schema([
                        'type' => 'object',
                        'properties' => $properties->mapWithKeys(fn ($fieldRules, $field) => [Str::after($field, "{$key}.*.") => $this->inferSchemaFromValidationRules($field, $rules->all())])->all(),
                        'required' => $properties->map(fn ($fieldRules, $field) => collect($fieldRules)->contains('required') || collect($fieldRules)->contains("required_with:{$key}") ? Str::after($field, "{$key}.*.") : null)->filter()->values(),
                        'additionalProperties' => false,
                    ]);
                    if (empty($itemSchema->required)) {
                        unset($itemSchema->required);
                    }
                    $schema->items = $itemSchema;
                    break;
                case is_string($rule) && Str::startsWith($rule, 'array:'):
                    $properties = explode(',', explode(':', $rule)[1]);
                    $schema = $this->setSchemaType($schema, 'object');
                    $schema->properties = collect($properties)->mapWithKeys(fn ($property) => [$property => $this->inferSchemaFromValidationRules("{$key}.{$property}", $rules->all())])->all();
                    $schema->required = collect($properties)->map(function ($property) use ($key, $rules) {
                        $fieldRules = collect($rules->get("{$key}.{$property}"));

                        return ($fieldRules->contains('required') || $fieldRules->contains("required_with:{$key}")) ? $property : null;
                    })->filter()->all();
                    if (empty($schema->required)) {
                        unset($schema->required);
                    }
                    $schema->additionalProperties = false;
                    break;
                case is_string($rule) && (Str::startsWith($rule, 'min:') || Str::startsWith($rule, 'size:')):
                    $attribute = match (true) {
                        $rulesForKey->contains('numeric') || $rulesForKey->contains('integer') => 'minimum',
                        $rulesForKey->contains('array') => 'minItems',
                        default => 'minLength',
                    };
                    $val = explode(':', $rule)[1];
                    $schema->{$attribute} = $attribute === 'minimum' ? (float) $val : (int) $val;
                    break;
                case is_string($rule) && Str::startsWith($rule, 'max:'):
                    $attribute = match (true) {
                        $rulesForKey->contains('numeric') || $rulesForKey->contains('integer') => 'maximum',
                        $rulesForKey->contains('array') => 'maxItems',
                        default => 'maxLength',
                    };
                    $val = explode(':', $rule)[1];
                    $schema->{$attribute} = $attribute === 'maximum' ? (float) $val : (int) $val;
                    break;
                case is_string($rule) && Str::startsWith($rule, 'between:'):
                    $minAttribute = match (true) {
                        $rulesForKey->contains('numeric') || $rulesForKey->contains('integer') => 'minimum',
                        $rulesForKey->contains('array') => 'minItems',
                        default => 'minLength',
                    };
                    $maxAttribute = match (true) {
                        $rulesForKey->contains('numeric') || $rulesForKey->contains('integer') => 'maximum',
                        $rulesForKey->contains('array') => 'maxItems',
                        default => 'maxLength',
                    };
                    $range = explode(':', $rule)[1];
                    [$min, $max] = explode(',', $range);
                    $schema->{$minAttribute} = $minAttribute === 'minimum' ? (float) $min : (int) $min;
                    $schema->{$maxAttribute} = $maxAttribute === 'maximum' ? (float) $max : (int) $max;
                    break;
                case is_string($rule) && Str::startsWith($rule, 'url'):
                    $schema = $this->setSchemaType($schema, 'string');
                    $schema->format = 'url';
                    break;
                case is_string($rule) && Str::startsWith($rule, 'email'):
                    $schema = $this->setSchemaType($schema, 'string');
                    $schema->format = 'email';
                    break;
                case is_string($rule) && Str::startsWith($rule, 'regex:'):
                    $schema->pattern = preg_replace('/^(\W)(.*?)(\1)[a-z]*$/', '$2', explode(':', $rule)[1]);
                    break;
                case $rule instanceof Password:
                    $passwordRule = invade($rule);
                    $schema = $this->setSchemaType($schema, 'string');
                    $schema->minLength = $passwordRule->min;
                    if ($passwordRule->max) {
                        $schema->maxLength = $passwordRule->max;
                    }
                    $pattern = '^';
                    if ($passwordRule->mixedCase) {
                        $pattern .= '(?=(.*\p{Ll}+.*\p{Lu}.*)|(.*\p{Lu}+.*\p{Ll}.*))';
                    }
                    if ($passwordRule->letters) {
                        $pattern .= '(?=.*\pL.*)';
                    }
                    if ($passwordRule->symbols) {
                        $pattern .= '(?=.*(\p{Z}|\p{S}|\p{P}).*)';
                    }
                    if ($passwordRule->numbers) {
                        $pattern .= '(?=.*\pN.*)';
                    }
                    $minmax = $passwordRule->max ? "{$passwordRule->min},{$passwordRule->max}" : $passwordRule->min . ',';
                    $pattern .= ".{{$minmax}}$";
                    $schema->pattern = $pattern;
                    break;
                case $rule instanceof \Illuminate\Validation\Rules\Enum:
                    $enumRule = invade($rule);
                    /** @var class-string<\BackedEnum> $enumClass */
                    $enumClass = $enumRule->type;
                    $schema = Enum::make($enumClass)
                        ->filter(fn ($case) => $enumRule->isDesirable($case))
                        ->nullable($rulesForKey->contains('nullable'))
                        ->build();
                    continue 2;
            }
        }

        return $schema;
    }

    private function makeNullable(Schema $schema): Schema
    {
        $types = $schema->type ? Arr::wrap($schema->type) : [];
        $types[] = 'null';
        $types = array_unique($types);

        if (count($types) === 1) {
            $schema->type = $types[0];
        } else {
            $schema->type = $types;
        }

        return $schema;
    }

    private function setSchemaType(Schema $schema, string $type): Schema
    {
        if (is_array($schema->type) && in_array('null', $schema->type)) {
            $schema->type = [$type, 'null'];

            return $schema;
        }
        $schema->type = $type;

        return $schema;
    }
}
