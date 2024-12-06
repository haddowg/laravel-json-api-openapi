<?php

return [
    'my-resource' => [
        'actions' => [
            'index' => [
                'description' => 'Fetch all My Resources',
                'summary' => 'Fetch all My Resources',
            ],
            // ...
            'my-custom-action' => [
                'description' => 'My custom action description',
                'summary' => 'My custom action summary',
            ],
        ],
        'parameters' => [
            'resource-id' => 'Custom id path parameter description for my resource',
            'sort' => 'Sort the my resources by one or more fields',
            'filters' => [
                'filter1' => 'Filter 1 description',
                'filter2' => 'Filter 2 description',
                // ...
            ],
        ],
        'pagination' => [
            // ...
            'cursor' => [
                'parameters' => [
                    'limit' => 'The number of my resources resources to return per page of results',
                    'before' => 'Show my resources before the provided cursor',
                    'after' => 'Show my resources after the provided cursor',
                ],
                'meta' => [
                ],
            ],
        ],
    ],
    // ...
];
