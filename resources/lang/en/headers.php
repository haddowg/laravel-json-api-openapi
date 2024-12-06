<?php

return [
    'retryAfter' => 'The number of seconds to wait before making a new request',
    'xRateLimit' => [
        'limit' => 'The number of allowed requests in the current period',
        'remaining' => 'The number of remaining requests in the current period',
        'reset' => 'The number of seconds until the rate limit period resets',
    ],
];
