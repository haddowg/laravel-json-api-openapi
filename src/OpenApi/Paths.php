<?php

namespace haddowg\JsonApiOpenApi\OpenApi;

use cebe\openapi\spec\Operation as SpecOperation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Paths as SpecPaths;
use haddowg\JsonApiOpenApi\Concerns\BuilderAware;
use haddowg\JsonApiOpenApi\Descriptors\Route as RouteDescriptor;
use haddowg\JsonApiOpenApi\Facades\JsonApiOpenApi;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation;
use haddowg\JsonApiOpenApi\OpenApi\Paths\Operation\Parameters;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use LaravelJsonApi\Contracts\Routing\Route as LaravelApiRouteContract;
use LaravelJsonApi\Laravel\Routing\Route as LaravelApiRoute;

readonly class Paths
{
    use BuilderAware;

    public static function make(): self
    {
        return new self();
    }

    /**
     * @return SpecPaths<int, PathItem>
     */
    public function build(): SpecPaths
    {
        return new SpecPaths(
            $this->resolveRoutes()
                ->sortKeys()
                ->map(fn (Collection $routes) => $this->pathItem($routes))
                ->filter()
                ->toArray(),
        );
    }

    /**
     * @return Collection<string,Collection<int,RouteDescriptor>>
     */
    private function resolveRoutes(): Collection
    {
        $routes = $this->getRoutes();
        if ($resolver = JsonApiOpenApi::getRouteResolver()) {
            return call_user_func($resolver, $routes, $this->builder()->server());
        }

        return $routes;
    }

    /**
     * @return Collection<string,Collection<int,RouteDescriptor>>
     */
    private function getRoutes(): Collection
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (IlluminateRoute $route) => Str::startsWith($route->getName(), $this->builder()->server()->name()))
            ->each(fn (IlluminateRoute $route) => $route->bind(request()))
            ->map(fn (IlluminateRoute $route): RouteDescriptor => new RouteDescriptor($this->builder(), $route))
            ->mapToGroups(fn (RouteDescriptor $route) => [$route->uri() => $route]);
    }

    /**
     * @param Collection<int, RouteDescriptor> $routes
     */
    private function pathItem(Collection $routes): ?PathItem
    {
        /** @var RouteDescriptor $route */
        $route = $routes->first();

        $parameters = array_filter([
            $this->builder()->components()->getParameter('JsonApi:acceptInHeader'),
            Parameters::make($route)->resourceId(),
        ]);

        $this->builder()->output()?->writeln("<info> - {$route->uri()}</info>");

        $operations = $routes->mapWithKeys(fn (RouteDescriptor $route) => $this->operation($route))->sortKeys()->toArray();
        if (empty($operations)) {
            $this->builder()->output()?->writeln('<error>   Unable to generate any operations, skipping path.</error>');

            return null;
        }

        return new PathItem([...$operations, 'parameters' => $parameters]);
    }

    /**
     * @return array<string, SpecOperation>
     */
    private function operation(RouteDescriptor $route): array
    {
        $this->builder()->container()->instance(
            LaravelApiRouteContract::class,
            new LaravelApiRoute($this->builder()->container(), $this->builder()->server(), $route->getRoute()),
        );

        $method = $route->method();

        if (!$route->resourceType()) {
            $this->builder()->output()?->writeln(sprintf('<error>    - %s %s : Unable to resolve resource type</error>', strtoupper($route->method()), $route->getName()));

            return [];
        }
        $operation = Operation::make($route);
        if (!$operation) {
            $this->builder()->output()?->writeln(sprintf('<error>    - %s %s : Unable to resolve resource action</error>', strtoupper($route->method()), $route->getName()));

            return [];
        }

        return [
            $method => $operation->build(),
        ];
    }
}
