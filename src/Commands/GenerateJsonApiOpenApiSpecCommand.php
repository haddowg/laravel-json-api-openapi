<?php

namespace haddowg\JsonApiOpenApi\Commands;

use haddowg\JsonApiOpenApi\Enums\OutputFormat;
use haddowg\JsonApiOpenApi\Facades\JsonApiOpenApi;
use haddowg\JsonApiOpenApi\Generator\Generator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

class GenerateJsonApiOpenApiSpecCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'jsonapi:docs {server} {--f|format=json : The format of the output (json or yaml)} {--o|output= : The filename to write the output to}';

    public $description = 'Generate OpenAPI documentation for your JSON:API';

    public function handle(Generator $generator): int
    {
        JsonApiOpenApi::withOutput($this->output);

        $this->info("Generating OpenAPI documentation for server: {$this->argument('server')}");

        $filename = $this->option('output') ?? $this->argument('server') . '.' . $this->option('format');

        $generator->write(
            $this->argument('server'),
            $filename,
            OutputFormat::tryFrom($this->option('format')) ?? OutputFormat::json,
        );

        $this->info("OpenAPI documentation generated and written to: {$filename}");

        return self::SUCCESS;
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'server' => 'Which JSON:API server would you like to generate documentation for?',
        ];
    }
}
