<?php

namespace haddowg\JsonApiOpenApi\Descriptors\Pagination;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema;
use haddowg\JsonApiOpenApi\Enums\DefaultSchema;

readonly class PagePaginator extends Paginator
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
                'description' => $this->getTranslatedPaginationAttribute('parameters.perPageKey'),
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
                'name' => "page[{$this->paginator->pageKey}]",
                'in' => 'query',
                'description' => $this->getTranslatedPaginationAttribute('parameters.pageKey'),
                'required' => false,
                'allowEmptyValue' => false,
                'schema' => [
                    'type' => 'integer',
                    'default' => 1,
                    'example' => 1,
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
                'example' => $this->route->url() . "?page[{$this->paginator->pageKey}]=1&page[{$this->getPerPageKey()}]=" . $this->defaultPerPage(),
            ],
            'last' => [
                'description' => $this->getTranslatedPaginationAttribute('links.last'),
                'type' => 'string',
                'format' => 'url',
                'example' => $this->route->url() . "?page[{$this->paginator->pageKey}]=4&page[{$this->getPerPageKey()}]=" . $this->defaultPerPage(),
            ],
            'prev' => [
                'description' => $this->getTranslatedPaginationAttribute('links.prev'),
                'type' => 'string',
                'format' => 'url',
                'example' => $this->route->url() . "?page[{$this->paginator->pageKey}]=1&page[{$this->getPerPageKey()}]=" . $this->defaultPerPage(),
            ],
            'next' => [
                'description' => $this->getTranslatedPaginationAttribute('links.next'),
                'type' => 'string',
                'format' => 'url',
                'example' => $this->route->url() . "?page[{$this->paginator->pageKey}]=3&page[{$this->getPerPageKey()}]=" . $this->defaultPerPage(),
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
            $this->applyMetaCase('currentPage') => [
                'description' => $this->getTranslatedPaginationAttribute('meta.currentPage'),
                'type' => 'integer',
                'example' => 2,
            ],
            $this->applyMetaCase('from') => [
                'description' => $this->getTranslatedPaginationAttribute('meta.from'),
                'type' => 'integer',
                'example' => $this->defaultPerPage() + 1,
            ],
            $this->applyMetaCase('lastPage') => [
                'description' => $this->getTranslatedPaginationAttribute('meta.lastPage'),
                'type' => 'integer',
                'example' => 4,
            ],
            $this->applyMetaCase('perPage') => [
                'description' => $this->getTranslatedPaginationAttribute('meta.perPage'),
                'type' => 'integer',
                'example' => $this->defaultPerPage(),
            ],
            $this->applyMetaCase('to') => [
                'description' => $this->getTranslatedPaginationAttribute('meta.to'),
                'type' => 'integer',
                'example' => $this->defaultPerPage() * 2,
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
        return $this->paginator->perPageKey;
    }

    protected function translationKey(): string
    {
        return 'page';
    }
}
