<?php

namespace haddowg\JsonApiOpenApi;

use haddowg\JsonApiOpenApi\Contracts\OpenApiBuilderContract;
use haddowg\JsonApiOpenApi\Descriptors\Route;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Server\Concerns\ServerAware;

/**
 * @phpstan-type RouteCollection Collection<string,Collection<int,Route>>
 */
class JsonApiOpenApi
{
    use ForwardsCalls;
    use ServerAware {
        withServer as protected _withServer;
    }

    public const VERSION_1_0 = '1.0';
    public const VERSION_1_1 = '1.1';

    /** @var (callable(RouteCollection, Server): RouteCollection)|null */
    private static $routeResolver;

    private ?OpenApiBuilderContract $builder;
    private ?OutputStyle $output = null;

    /**
     * @return (callable(RouteCollection, Server): RouteCollection)|null
     */
    public function getRouteResolver(): ?callable
    {
        return self::$routeResolver;
    }

    /**
     * @param (callable(RouteCollection, Server): RouteCollection)|null $routeResolver
     */
    public function withRouteResolver(?callable $routeResolver): self
    {
        self::$routeResolver = $routeResolver;

        return $this;
    }

    /**
     * Use a named server, or the default server if name is empty.
     *
     * @return $this
     */
    public function withServer(?string $name): self
    {
        $this->builder = null;

        return $this->_withServer($name);
    }

    public function withOutput(OutputStyle $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function builder(): OpenApiBuilderContract
    {
        if ($this->builder) {
            return $this->builder;
        }
        $this->builder = app()->makeWith(OpenApiBuilderContract::class, ['server' => $this->server()]);
        $this->builder->withOutput($this->output);

        return $this->builder;
    }
}
