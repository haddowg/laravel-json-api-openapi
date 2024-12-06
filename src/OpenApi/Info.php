<?php

namespace haddowg\JsonApiOpenApi\OpenApi;

use cebe\openapi\spec\Info as SpecInfo;
use haddowg\JsonApiOpenApi\Attributes\Info as InfoAttribute;
use haddowg\JsonApiOpenApi\Concerns\PerformsTranslation;

readonly class Info
{
    use PerformsTranslation;

    public static function make(): self
    {
        return new self();
    }

    public function build(): SpecInfo
    {
        if ($fromAttribute = $this->fromAttribute()) {
            return $fromAttribute;
        }

        return new SpecInfo([
            'title' => $this->title(),
            'description' => $this->description(),
            'version' => $this->version(),
        ]);
    }

    private function fromAttribute(): ?SpecInfo
    {
        $server = $this->builder()->server();

        $reflection = new \ReflectionClass($server);
        $infoAttribute = $reflection->getAttributes(InfoAttribute::class);

        if (!empty($infoAttribute)) {
            $info = $infoAttribute[0]->newInstance()->toSpec();
            $info->version = $this->version();

            if (empty($info->description)) {
                $info->description = $this->description();
            }

            if (empty($info->title)) {
                $info->title = $this->title();
            }

            return $info;
        }

        return null;
    }

    private function version(): string
    {
        return $this->builder()->server()->jsonApi()->version();
    }

    private function title(): string
    {
        return $this->getTranslation('info.title');
    }

    private function description(): string
    {
        return $this->getTranslation('info.description');
    }
}
