<?php

namespace haddowg\JsonApiOpenApi\Attributes;

use cebe\openapi\SpecBaseObject;

interface SpecAttribute
{
    public function toSpec(): SpecBaseObject;
}
