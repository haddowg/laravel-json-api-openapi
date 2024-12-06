<?php

namespace haddowg\JsonApiOpenApi\Descriptors\Pagination;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema;
use Illuminate\Support\Collection;
use LaravelJsonApi\Contracts\Pagination\Paginator as PaginatorContract;

readonly class MultiPaginator extends Paginator
{
    public function isDefault(): bool
    {
        return $this->paginators()->some(fn (Paginator $paginator) => $paginator->isDefault());
    }

    /**
     * @return array<Parameter>
     */
    public function parameters(): array
    {
        return $this->paginators()
            ->map(fn (Paginator $paginator) => $paginator->parameters())
            ->flatten()
            ->all();
    }

    public function links(): Schema
    {
        return new Schema([
            'allOf' => $this->paginators()
                ->map(fn (Paginator $paginator) => $paginator->links())
                ->flatten()
                ->all(),
        ]);
    }

    public function meta(): Schema
    {
        return new Schema([
            'allOf' => $this->paginators()
                ->map(fn (Paginator $paginator) => $paginator->meta())
                ->flatten()
                ->all(),
        ]);
    }

    /**
     * @return Collection<int,Paginator>
     */
    protected function paginators()
    {
        /** @var array<int,PaginatorContract> $paginators */
        $paginators = $this->paginator->paginators;

        return collect($paginators)->map(fn ($paginator) => Paginator::make($this->openApiBuilder, $this->route, $paginator));
    }

    protected function getPerPageKey(): string
    {
        // unused as we defer to internal paginators
        return '...';
    }

    protected function translationKey(): string
    {
        // unused as we defer to internal paginators
        return '...';
    }
}
