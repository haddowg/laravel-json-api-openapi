<?php

namespace haddowg\JsonApiOpenApi\Concerns;

use haddowg\JsonApiOpenApi\Descriptors\Route;
use Illuminate\Support\Str;
use LaravelJsonApi\Contracts\Schema\Relation;

/**
 * @property Route $route
 */
trait PerformsTranslationForRoute
{
    use PerformsTranslation {
        getTranslationForResource as _getTranslationForResource;
    }

    /**
     * @return array<string, string>
     */
    protected function getResourceTranslationReplacements(string $resourceType): array
    {
        $replacements = [
            'resource-type' => $resourceType,
            'resource-type-singular' => Str::singular($resourceType),
            'action' => $this->route->getActionMethod(),
        ];

        if ($this->route->hasRelation()) {
            $replacements = array_merge($replacements, $this->getResourceRelationshipTranslationReplacements($this->route->relation(), $this->route->routeResourceType()));
        }

        return array_merge($this->getTranslationReplacements(), $replacements);
    }

    /**
     * @param array<string, string> $replacements
     */
    protected function getTranslationForResource(string $key, ?string $resourceType = null, array $replacements = [], ?string $default = null): ?string
    {
        $resourceType ??= $this->route->resourceType() ?? 'unknown';

        return $this->_getTranslationForResource($key, $resourceType, $replacements, $default);
    }

    /**
     * @return array<string, string>
     */
    protected function getResourceRelationshipTranslationReplacements(Relation $relation, string $parent): array
    {
        return [
            'relation' => $relation->name(),
            'related-resource-type' => $relation->toOne() ? Str::singular($relation->inverse()) : $relation->inverse(),
            'parent-resource-type' => $parent,
            'parent-resource-type-singular' => Str::singular($parent),
        ];
    }
}
