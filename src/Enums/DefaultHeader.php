<?php

namespace haddowg\JsonApiOpenApi\Enums;

enum DefaultHeader: string
{
    case XRateLimitLimit = 'X-Rate-Limit-Limit';
    case XRateLimitRemaining = 'X-Rate-Limit-Remaining';
}
