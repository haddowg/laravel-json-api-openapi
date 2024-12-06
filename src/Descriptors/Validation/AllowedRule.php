<?php

namespace haddowg\JsonApiOpenApi\Descriptors\Validation;

use Illuminate\Support\Collection;
use LaravelJsonApi\Validation\Rules\AbstractAllowedRule;

class AllowedRule
{
    /**
     * @var Collection<int,string>
     */
    private Collection $allowed;

    public function __construct(
        AbstractAllowedRule $allowedRule,
    ) {
        $reflectionClass = new \ReflectionClass($allowedRule);
        $parentClass = $reflectionClass->getParentClass();
        if (!$parentClass) {
            throw new \InvalidArgumentException('AllowedRule must extend AbstractAllowedRule');
        }
        $property = $parentClass->getProperty('allowed');
        $this->allowed = $property->getValue($allowedRule);
    }

    public static function make(AbstractAllowedRule $allowedRule): self
    {
        return new self($allowedRule);
    }

    /**
     * @return Collection<int,string>
     */
    public function getAllowed(): Collection
    {
        return $this->allowed;
    }
}
