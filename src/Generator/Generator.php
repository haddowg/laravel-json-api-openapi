<?php

namespace haddowg\JsonApiOpenApi\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\Writer;
use haddowg\JsonApiOpenApi\Contracts\GeneratorContract;
use haddowg\JsonApiOpenApi\Enums\OutputFormat;
use haddowg\JsonApiOpenApi\Facades\JsonApiOpenApi;
use LaravelJsonApi\Core\JsonApiService;

class Generator implements GeneratorContract
{
    public function __construct(protected readonly JsonApiService $jsonApiService) {}

    public function generate(string $serverKey, OutputFormat $format = OutputFormat::json): string
    {
        return match ($format) {
            OutputFormat::json => Writer::writeToJson($this->generateSpec($serverKey), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            OutputFormat::yaml => Writer::writeToYaml($this->generateSpec($serverKey)),
        };
    }

    public function write(string $serverKey, string $filename, OutputFormat $format = OutputFormat::json): void
    {
        if (file_put_contents($filename, $this->generate($serverKey, $format)) === false) {
            throw new \RuntimeException("Failed to write to file: {$filename}");
        }
    }

    protected function generateSpec(string $serverKey): OpenApi
    {
        return JsonApiOpenApi::withServer($serverKey)->builder()->build();
    }
}
