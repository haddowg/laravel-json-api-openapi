<?php

namespace haddowg\JsonApiOpenApi\Attributes;

use cebe\openapi\spec\Contact;
use cebe\openapi\spec\Info as SpecInfo;
use cebe\openapi\spec\License;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Info implements SpecAttribute
{
    public function __construct(
        private ?string $title,
        private ?string $description,
        private ?string $termsOfService,
        private ?Contact $contact,
        private ?License $license,
    ) {}

    public function toSpec(): SpecInfo
    {
        return new SpecInfo(array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'termsOfService' => $this->termsOfService,
            'contact' => $this->contact,
            'license' => $this->license,
            /**
             * This is hardcoded here due to the classes validation but will be ignored
             * The version can be specified via your Server class by modifying the jsonApi method.
             */
            'version' => '1.0',
        ]));
    }
}
