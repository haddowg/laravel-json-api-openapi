<?php

namespace haddowg\JsonApiOpenApi\Descriptors\Pagination;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema as SpecSchema;
use haddowg\JsonApiOpenApi\Concerns\InfersSchema;
use haddowg\JsonApiOpenApi\Concerns\PerformsTranslationForRoute;
use haddowg\JsonApiOpenApi\Contracts\OpenApiBuilderContract as OpenApiBuilder;
use haddowg\JsonApiOpenApi\Descriptors\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Contracts\Pagination\Paginator as PaginatorContract;
use LaravelJsonApi\Core\Pagination\Concerns\HasPageNumbers;
use LaravelJsonApi\Core\Support\Str as JsonApiStr;
use LaravelJsonApi\Eloquent\Pagination\CursorPagination;
use LaravelJsonApi\Eloquent\Pagination\MultiPagination;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Spatie\Invade\Invader;
use Spatie\Invade\StaticInvader;

abstract readonly class Paginator
{
    use PerformsTranslationForRoute;
    use InfersSchema;

    /**
     * @var Invader<PaginatorContract>|StaticInvader
     */
    protected mixed $paginator;

    public function __construct(protected OpenApiBuilder $openApiBuilder, protected Route $route, PaginatorContract $paginator)
    {
        $this->paginator = invade($paginator);
    }

    final public static function make(OpenApiBuilder $openApiBuilder, Route $route, PaginatorContract $paginator): ?Paginator
    {
        return match (true) {
            $paginator instanceof PagePagination
            || in_array(HasPageNumbers::class, class_uses_recursive(get_class($paginator))) => new PagePaginator($openApiBuilder, $route, $paginator),
            $paginator instanceof CursorPagination => new CursorPaginator($openApiBuilder, $route, $paginator),
            $paginator instanceof MultiPagination => new MultiPaginator($openApiBuilder, $route, $paginator),
            default => null,
        };
    }

    public function isDefault(): bool
    {
        return array_key_exists($this->getPerPageKey(), $this->getDefaults());
    }

    /**
     * @return array<int, Parameter>
     */
    abstract public function parameters(): array;

    abstract public function links(): SpecSchema;

    abstract public function meta(): SpecSchema;

    public function paginate(): Page
    {
        $resources = $this->route->resources()->collection($this->defaultPerPage());

        return $this->paginator->paginate(
            $this->route->modelInstance()::query()
                ->withoutGlobalScopes()
                ->whereKey(
                    $resources->map(
                        function ($resource) {
                            /** @var Model $model */
                            $model = $resource->resource;

                            return $model->getKey();
                        },
                    )->all(),
                ),
            [$this->getPerPageKey() => $this->defaultPerPage()],
        );
    }

    /**
     * @return array<string,mixed>
     */
    protected function getDefaults(): array
    {
        $schema = $this->route->schema();
        if ($schema instanceof Schema) {
            return $schema->defaultPagination();
        }

        return [];
    }

    protected function defaultPerPage(): int
    {
        return $this->paginator->defaultPerPage ?? Arr::get($this->getDefaults(), $this->getPerPageKey()) ?? 15;
    }

    abstract protected function getPerPageKey(): string;

    protected function applyMetaCase(string $key): string
    {
        return match ($this->paginator->metaCase) {
            'snake' => JsonApiStr::snake($key),
            'dash' => JsonApiStr::dasherize($key),
            default => $key,
        };
    }

    abstract protected function translationKey(): string;

    protected function getTranslatedPaginationAttribute(string $key): string
    {
        if ($fromResource = $this->getTranslationForResource("pagination.{$this->translationKey()}.{$key}")) {
            return $fromResource;
        }

        return $this->getTranslation("pagination.{$this->translationKey()}.{$key}");
    }
}
