<?php

namespace haddowg\JsonApiOpenApi\Attributes\Actions;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
readonly class Update implements ResourceAction
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
                if (!in_array($responseCode, [200, 202, 204])) {
                    throw new \InvalidArgumentException("Invalid response code for resource update ({$responseCode}). See https://jsonapi.org/format/#crud-creating-responses");
                }
            }
        }

        $this->responseCodes = $responseCodes ?? [200];
    }
}
