<?php

namespace haddowg\JsonApiOpenApi\Enums;

enum DefaultSchema: string
{
    case JsonApi = 'JsonApi:jsonapi';
    case Meta = 'JsonApi:meta';
    case Links = 'JsonApi:links';
    case LinkObject = 'JsonApi:linkObject';
    case Errors = 'JsonApi:errors';
    case Error = 'JsonApi:error';
    case Failure = 'JsonApi:failure';
    case Id = 'JsonApi:resourceId';
    case Type = 'JsonApi:resourceType';
    case CursorPaginationMeta = 'JsonApi:cursor-pagination-meta';
    case PagePaginationMeta = 'JsonApi:page-pagination-meta';
    case CursorPaginationLinks = 'JsonApi:cursor-pagination-links';
    case PagePaginationLinks = 'JsonApi:page-pagination-links';
}
