<?php

namespace haddowg\JsonApiOpenApi\Contracts;

use haddowg\JsonApiOpenApi\Enums\OutputFormat;

interface GeneratorContract
{
    public function generate(string $serverKey, OutputFormat $format = OutputFormat::yaml): string;

    public function write(string $serverKey, string $filename, OutputFormat $format = OutputFormat::json): void;
}
