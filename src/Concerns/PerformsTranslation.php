<?php

namespace haddowg\JsonApiOpenApi\Concerns;

use Illuminate\Support\Str;

trait PerformsTranslation
{
    use BuilderAware;

    /**
     * @param array<string, string> $replacements
     */
    protected function getTranslation(string $key, array $replacements = [], ?string $default = null): string
    {
        $server = $this->builder()->server()->name();

        $value = $this->findTranslation([
            "jsonapi.{$server}.{$key}",
            "jsonapi.{$key}",
            "jsonapi-openapi::{$key}",
        ], array_merge($this->getTranslationReplacements(), $replacements), $default);

        return is_string($value) ? $value : $key;
    }

    /**
     * @return array<string, string>
     */
    protected function getResourceTranslationReplacements(string $resourceType): array
    {
        $replacements = [
            'resource-type' => $resourceType,
            'resource-type-singular' => Str::singular($resourceType),
        ];

        return array_merge($this->getTranslationReplacements(), $replacements);
    }

    /**
     * @param array<string, string> $replacements
     */
    protected function getTranslationForResource(string $key, string $resourceType, array $replacements = [], ?string $default = null): ?string
    {
        $server = $this->builder()->server()->name();

        return $this->findTranslation(array_filter([
            $resourceType ? "{$server}.resources.{$resourceType}.{$key}" : null,
            "{$server}.resource.{$key}",
            $resourceType ? "jsonapi.resources.{$resourceType}.{$key}" : null,
            "jsonapi.resource.{$key}",
            "jsonapi-openapi::resource.{$key}",
        ]), array_merge($this->getResourceTranslationReplacements($resourceType), $replacements), $default);
    }

    /**
     * @param array<int, string> $keys
     * @param array<string, string> $replacements
     */
    private function findTranslation(array $keys, array $replacements = [], ?string $default = null): ?string
    {
        foreach ($keys as $key) {
            $line = trans($key, $replacements);
            if ($line !== $key && is_string($line)) {
                return $line;
            }
        }

        return $default;
    }

    /**
     * @return array<string, string>
     */
    private function getTranslationReplacements(): array
    {
        return [
            'server' => $this->builder()->server()->name(),
            'app' => config('app.name'),
            'jsonapi-version' => $this->builder()->server()->jsonApi()->version(),
        ];
    }
}
