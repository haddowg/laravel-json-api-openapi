<?php

namespace haddowg\JsonApiOpenApi;

use haddowg\JsonApiOpenApi\Commands\GenerateJsonApiOpenApiSpecCommand;
use haddowg\JsonApiOpenApi\Contracts\GeneratorContract;
use haddowg\JsonApiOpenApi\Contracts\OpenApiBuilderContract;
use haddowg\JsonApiOpenApi\Generator\Generator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class JsonApiOpenApiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('jsonapi-openapi')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasCommand(GenerateJsonApiOpenApiSpecCommand::class);

        $this->app->singleton(JsonApiOpenApi::class);
        $this->app->bind(GeneratorContract::class, Generator::class);
        $this->app->bind(OpenApiBuilderContract::class, OpenApiBuilder::class);
    }
}
