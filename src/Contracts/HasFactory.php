<?php

namespace haddowg\JsonApiOpenApi\Contracts;

use haddowg\JsonApiOpenApi\Descriptors\Route;

interface HasFactory
{
    public function factory(Route $route): ResourceFactory;
}
