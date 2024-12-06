<?php

namespace haddowg\JsonApiOpenApi;

use cebe\openapi\spec\OpenApi;
use haddowg\JsonApiOpenApi\Contracts\OpenApiBuilderContract;
use haddowg\JsonApiOpenApi\Events\Documented;
use haddowg\JsonApiOpenApi\Events\Documenting;
use haddowg\JsonApiOpenApi\OpenApi\Components;
use haddowg\JsonApiOpenApi\OpenApi\Info;
use haddowg\JsonApiOpenApi\OpenApi\Paths;
use haddowg\JsonApiOpenApi\OpenApi\ResourcesRepository;
use haddowg\JsonApiOpenApi\OpenApi\Tags;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use LaravelJsonApi\Contracts\Server\Server;

class OpenApiBuilder implements OpenApiBuilderContract
{
    protected const OPENAPI_VERSION = '3.1.0';
    private Components $components;
    private Tags $tags;
    private ResourcesRepository $resources;
    private int $hasherRoundsBackup;
    /** @var array<string,mixed> */
    private array $afterResolvingCallbacksBackup;
    private ?OutputStyle $output = null;

    public function __construct(
        private readonly Server $server,
        private readonly Container $container,
    ) {
        $this->container->instance(
            Server::class,
            $server,
        );
        $this->components = Components::make();
        $this->tags = Tags::make();
        $this->resources = new ResourcesRepository();
    }

    protected function setUp(): void
    {
        Queue::fake();
        DB::beginTransaction();
        $this->components->init();

        $container = invade($this->container);
        $this->afterResolvingCallbacksBackup = $container->afterResolvingCallbacks;
        $this->hasherRoundsBackup = config('hashing.bcrypt.rounds');
        config(['hashing.bcrypt.rounds' => 1]);
        $container->afterResolvingCallbacks = [];
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        Queue::clearResolvedInstances();
        $container = invade($this->container);
        $container->afterResolvingCallbacks = $this->afterResolvingCallbacksBackup;
        config(['hashing.bcrypt.rounds' => $this->hasherRoundsBackup]);
    }

    public function withOutput(OutputStyle $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function output(): ?OutputStyle
    {
        return $this->output;
    }

    public function server(): Server
    {
        return $this->server;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function components(): Components
    {
        return $this->components;
    }

    public function tags(): Tags
    {
        return $this->tags;
    }

    public function build(): OpenApi
    {
        $this->setUp();

        $this->documenting();

        $api = new OpenApi(
            [
                'openapi' => self::OPENAPI_VERSION,
                'info' => Info::make()->build(),
                //                'servers' => $this->serverBuilder->build(),
                'paths' => Paths::make()->build(),
                'components' => $this->components->build(),
                //                'security' => $this->security,
                'tags' => $this->tags->build(),
                //                'externalDocs' => $this->externalDocs,
            ],
        );

        $this->documented($api);

        $this->tearDown();

        return $api;
    }

    public function resources(): ResourcesRepository
    {
        return $this->resources;
    }

    protected function documenting(): void
    {
        if ($this->container->has(Dispatcher::class)) {
            $this->container->get(Dispatcher::class)->dispatch(new Documenting($this));
        }
        if (method_exists($this->server, 'documenting')) {
            $this->server->documenting($this);
        }
    }

    protected function documented(OpenApi $openApi): void
    {
        if ($this->container->has(Dispatcher::class)) {
            $this->container->get(Dispatcher::class)->dispatch(new Documented($this, $openApi));
        }
        if (method_exists($this->server, 'documented')) {
            $this->server->documented($this, $openApi);
        }
    }
}
