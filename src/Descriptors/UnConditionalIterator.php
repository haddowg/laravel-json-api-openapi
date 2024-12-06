<?php

namespace haddowg\JsonApiOpenApi\Descriptors;

use LaravelJsonApi\Contracts\Resources\JsonApiRelation;
use LaravelJsonApi\Contracts\Resources\Serializer\Attribute as SerializableAttribute;
use LaravelJsonApi\Contracts\Resources\Serializer\Relation as SerializableRelation;
use LaravelJsonApi\Contracts\Schema\Field;
use LaravelJsonApi\Core\Resources\ConditionalField;
use LaravelJsonApi\Core\Resources\ConditionalFields;

/**
 * @template TKey
 * @template TValue
 * @implements \IteratorAggregate<TKey,TValue>
 */
class UnConditionalIterator implements \IteratorAggregate
{
    /**
     * @param iterable<TKey,TValue> $data
     */
    public function __construct(private readonly iterable $data) {}

    public function getIterator(): \Traversable
    {
        return $this->cursor();
    }

    /**
     * @return array<TKey,TValue>
     */
    public function all(): array
    {
        return iterator_to_array($this);
    }

    public function cursor(): \Generator
    {
        foreach ($this->values() as $key => $value) {
            if ($value instanceof ConditionalField) {
                $value = $value->value();
            }

            if ($value instanceof SerializableAttribute || $value instanceof SerializableRelation) {
                yield $value->serializedFieldName() => $value;
                continue;
            }

            if ($value instanceof JsonApiRelation) {
                yield $value->fieldName() => $value;
                continue;
            }

            if ($value instanceof Field) {
                yield $value->name() => $value;
            }

            yield $key => $value;
        }
    }

    private function values(): \Generator
    {
        foreach ($this->data as $k => $value) {
            if ($value instanceof ConditionalFields) {
                yield from $value->values();
                continue;
            }

            yield $k => $value;
        }
    }
}
