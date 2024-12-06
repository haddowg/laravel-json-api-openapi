<?php

namespace haddowg\JsonApiOpenApi\Contracts;

use LaravelJsonApi\Core\Resources\JsonApiResource;

interface ResourceFactory
{
    /**
     * @return iterable<int,JsonApiResource>
     */
    public function collection(int $count): iterable;

    public function single(): JsonApiResource;
}
