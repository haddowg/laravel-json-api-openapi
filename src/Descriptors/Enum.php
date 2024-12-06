<?php

namespace haddowg\JsonApiOpenApi\Descriptors;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use haddowg\JsonApiOpenApi\Attributes\AttributeHelper;
use haddowg\JsonApiOpenApi\Attributes\Description;
use haddowg\JsonApiOpenApi\Attributes\Name;
use haddowg\JsonApiOpenApi\Concerns\BuilderAware;

class Enum
{
    use BuilderAware;
    private bool $canRef = true;
    private bool $nullable = false;
    private ?string $name;
    /** @var array<int, \BackedEnum> */
    private array $cases;

    /**
     * @param class-string<\BackedEnum> $enum
     */
    public function __construct(private readonly string $enum, ?string $name = null)
    {
        $this->cases = $enum::cases();
        $this->name = $name;
    }

    /**
     * @param class-string<\BackedEnum> $enum
     */
    public static function make(string $enum): self
    {
        return new self($enum);
    }

    public function filter(\Closure $filter): self
    {
        $this->cases = collect($this->cases)
            ->filter($filter)->all();
        if (count($this->cases) < count($this->enum::cases())) {
            $this->canRef = false;
        }

        return $this;
    }

    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;
        $this->canRef = $nullable ? false : $this->canRef;

        return $this;
    }

    public function build(): Schema|Reference
    {
        $name = $this->getName();
        if ($this->canRef && $this->builder()->components()->hasSchema($name)) {
            return $this->builder()->components()->getSchema($name);
        }

        $schema = new Schema([
            'type' => 'string',
        ]);

        $cases = collect($this->cases)
            ->map(fn ($enumCase) => $enumCase->value)->values()->toArray();

        if (empty($cases)) {
            return $this->nullable ? new Schema([
                'type' => 'null',
            ]) : $schema;
        }

        if (count($cases) === 1) {
            // @phpstan-ignore-next-line const not recognized on Schema but works
            $schema->const = $cases[0];

            return $schema;
        }

        $schema->enum = $cases;
        $schema->example = $cases[0];

        $found = false;
        // get description for each case from attributes
        $descriptions = collect((new \ReflectionEnum($this->enum))->getCases())->mapWithKeys(function ($case) use (&$found) {
            if ($description = AttributeHelper::getStringFromValueAttribute($case, Description::class)) {
                $found = true;

                return [$case->getBackingValue() => $description];
            }

            return [$case->getBackingValue() => $case->getBackingValue()];
        })->all();

        if ($found) {
            // @phpstan-ignore-next-line x-* attributes valid but not typed
            $schema->{'x-enum-descriptions'} = $descriptions;
        }

        if (!$this->canRef) {
            return $schema;
        }

        return $this->builder()->components()->addSchema($name, $schema);
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    private function getName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        if ($name = AttributeHelper::getStringFromValueAttribute($this->enum, Name::class)) {
            return $name;
        }

        return class_basename($this->enum);
    }
}
