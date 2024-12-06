<?php

namespace haddowg\JsonApiOpenApi\Contracts;

use cebe\openapi\spec\OpenApi;
use haddowg\JsonApiOpenApi\OpenApi\Components;
use haddowg\JsonApiOpenApi\OpenApi\ResourcesRepository;
use haddowg\JsonApiOpenApi\OpenApi\Tags;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Container\Container;
use LaravelJsonApi\Contracts\Server\Server;

interface OpenApiBuilderContract
{
    public function __construct(Server $server, Container $container);

    public function server(): Server;

    public function container(): Container;

    public function components(): Components;

    public function tags(): Tags;

    public function resources(): ResourcesRepository;

    public function build(): OpenApi;

    public function withOutput(OutputStyle $output): self;

    public function output(): ?OutputStyle;
}
