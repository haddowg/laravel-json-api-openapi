<?php

namespace haddowg\JsonApiOpenApi\Descriptors\Pagination;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema;
use haddowg\JsonApiOpenApi\Enums\DefaultSchema;

readonly class CursorPaginator extends Paginator
{
    /**
     * @return array<int, Parameter>
     */
    public function parameters(): array
    {
        $paginationRules = $this->route->validationRules()->pagination()->all();

        return [
            new Parameter([
                'name' => "page[{$this->getPerPageKey()}]",
                'in' => 'query',
                'description' => $this->getTranslatedPaginationAttribute('parameters.limit'),
                'required' => false,
                'allowEmptyValue' => false,
                'schema' => $this->inferSchemaFromValidationRules($this->getPerPageKey(), $paginationRules, new Schema([
                    'type' => 'integer',
                    'default' => $this->defaultPerPage(),
                    'example' => $this->defaultPerPage(),
                ])),
            ]),
            new Parameter([
                // @phpstan-ignore-next-line
                'name' => "page[{$this->paginator->before}]",
                'in' => 'query',
                'description' => $this->getTranslatedPaginationAttribute('parameters.before'),
                'required' => false,
                'allowEmptyValue' => false,
                'schema' => [
                    'type' => 'string',
                    'example' => 'eyJsb29wcy5oZWFkbGluZSI6IkFiIHJlcHVkaWFuZGFlIGlzdGUgcXVpLiI',
                ],
            ]),
            new Parameter([
                // @phpstan-ignore-next-line
                'name' => "page[{$this->paginator->after}]",
                'in' => 'query',
                'description' => $this->getTranslatedPaginationAttribute('parameters.after'),
                'required' => false,
                'allowEmptyValue' => false,
                'schema' => [
                    'type' => 'string',
                    'example' => 'wLTczOTUtYjMxNS03ZTU4M2I3ZWMxMzAiLCJfcG9pbnRzVG9OZXh0SXRlbX',
                ],
            ]),
        ];
    }

    public function links(): Schema
    {
        $links = [
            'first' => [
                'description' => $this->getTranslatedPaginationAttribute('links.first'),
                'type' => 'string',
                'format' => 'url',
                'example' => $this->route->url() . "?page[{$this->getPerPageKey()}]=" . $this->defaultPerPage(),
            ],
            'prev' => [
                'description' => $this->getTranslatedPaginationAttribute('links.prev'),
                'type' => 'string',
                'format' => 'url',
                'example' => $this->route->url() . "?page[{$this->paginator->before}]=eyJsb29wcy5oZWFkbGluZSI6IkFiIHJlcHVkaWFuZGFlIGlzdGUgcXVpLiI&page[{$this->getPerPageKey()}]=" . $this->defaultPerPage(),
            ],
            'next' => [
                'description' => $this->getTranslatedPaginationAttribute('links.next'),
                'type' => 'string',
                'format' => 'url',
                'example' => $this->route->url() . "?page[{$this->paginator->after}]=wLTczOTUtYjMxNS03ZTU4M2I3ZWMxMzAiLCJfcG9pbnRzVG9OZXh0SXRlbX&page[{$this->getPerPageKey()}]=" . $this->defaultPerPage(),
            ],
        ];

        return new Schema([
            'allOf' => [
                $this->openApiBuilder->components()->getSchema(DefaultSchema::Links),
                new Schema([
                    'type' => 'object',
                    'properties' => $links,
                ]),
            ],
        ]);
    }

    public function meta(): Schema
    {
        $meta = [
            $this->applyMetaCase('from') => [
                'description' => $this->getTranslatedPaginationAttribute('meta.from'),
                'type' => 'string',
                'example' => 'eyJ1dWlkIjoiMDE5M2I1NDctNDk0OC03MzFkLThiMmQtZGFkM2Y5MDA3MGQyIiwiX3BvaW50c1RvTmV4dEl0ZW1zIjpmYWxzZX0',
            ],
            $this->applyMetaCase('hasMore') => [
                'description' => $this->getTranslatedPaginationAttribute('meta.hasMore'),
                'type' => 'boolean',
                'example' => true,
            ],
            $this->applyMetaCase('perPage') => [
                'description' => $this->getTranslatedPaginationAttribute('meta.perPage'),
                'type' => 'integer',
                'example' => $this->defaultPerPage(),
            ],
            $this->applyMetaCase('to') => [
                'description' => $this->getTranslatedPaginationAttribute('meta.to'),
                'type' => 'string',
                'example' => 'eyJ1dWlkIjoiMDE5M2I1NDYtYzc4NS03MTY5LWFiYjEtMzQ4MDU1NjNlMjE1IiwiX3BvaW50c1RvTmV4dEl0ZW1zIjp0cnVlfQ',
            ],
            $this->applyMetaCase('total') => [
                'description' => $this->getTranslatedPaginationAttribute('meta.total'),
                'type' => 'integer',
                'example' => ($this->defaultPerPage() * 3) + 1,
            ],
        ];

        $metaKey = $this->paginator->metaKey;
        if ($metaKey === null) {
            return new Schema(array_filter([
                'description' => $this->getTranslation('schema.meta'),
                'type' => 'object',
                'properties' => $meta,
                'required' => $this->isDefault() ? array_keys($meta) : null,
                'additionalProperties' => true,
            ]));
        }

        return new Schema(array_filter([
            'description' => $this->getTranslation('schema.meta'),
            'type' => 'object',
            'properties' => [
                $metaKey => [
                    'description' => $this->getTranslatedPaginationAttribute('meta.description'),
                    'type' => 'object',
                    'properties' => $meta,
                    'required' => array_keys($meta),
                ],
            ],
            'required' => $this->isDefault() ? [$metaKey] : null,
            'additionalProperties' => true,
        ]));
    }

    protected function getPerPageKey(): string
    {
        // @phpstan-ignore-next-line
        return $this->paginator->limit;
    }

    protected function translationKey(): string
    {
        return 'cursor';
    }
}
