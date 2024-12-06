<?php

namespace haddowg\JsonApiOpenApi\Enums;

enum ResourceAction
{
    case FETCH_ONE;
    case FETCH_MANY;
    case CREATE;
    case UPDATE;
    case DESTROY;
}
