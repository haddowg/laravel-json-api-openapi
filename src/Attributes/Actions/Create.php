<?php

namespace haddowg\JsonApiOpenApi\Attributes\Actions;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
readonly class Create implements ResourceAction
{
    /**
     * @var int[]
     */
    public array $responseCodes;

    /**
     * @param int[]|null $responseCodes
     */
    public function __construct(
        public ?string $resourceType = null,
        ?array $responseCodes = null,
        public ?string $returnResourceType = null,
    ) {
        if ($responseCodes) {
            foreach ($responseCodes as $responseCode) {
                if (!in_array($responseCode, [201, 202, 204])) {
                    throw new \InvalidArgumentException("Invalid response code for resource creation ({$responseCode}). See https://jsonapi.org/format/#crud-creating-responses");
                }
            }
        }

        $this->responseCodes = $responseCodes ?? [201];
    }
}
