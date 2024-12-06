<?php

return [
    '401' => [
        'description' => 'Unauthorized',
        'example' => [
            'jsonapi' => [
                'version' => ':jsonapi-version',
            ],
            'errors' => [
                [
                    'title' => 'Unauthorized',
                    'status' => '401',
                ],
            ],
        ],
    ],
    '403' => [
        'description' => 'Forbidden',
        'example' => [
            'jsonapi' => [
                'version' => ':jsonapi-version',
            ],
            'errors' => [
                [
                    'title' => 'Forbidden',
                    'status' => '403',
                ],
            ],
        ],
    ],
    '404' => [
        'description' => 'Not Found',
        'example' => [
            'jsonapi' => [
                'version' => ':jsonapi-version',
            ],
            'errors' => [
                [
                    'title' => 'Not Found',
                    'status' => '404',
                ],
            ],
        ],
    ],
    '406' => [
        'description' => '[Not Acceptable](https://jsonapi.org/format/#content-negotiation)',
        'example' => [
            'jsonapi' => [
                'version' => ':jsonapi-version',
            ],
            'errors' => [
                [
                    'title' => 'Not Acceptable',
                    'status' => '406',
                ],
            ],
        ],
    ],
    '409' => [
        'description' => 'Conflict',
        'example' => [
            'jsonapi' => [
                'version' => ':jsonapi-version',
            ],
            'errors' => [
                [
                    'title' => 'Conflict',
                    'status' => '409',
                ],
            ],
        ],
    ],
    '415' => [
        'description' => '[Unsupported Media Type](https://jsonapi.org/format/#content-negotiation)',
        'example' => [
            'jsonapi' => [
                'version' => ':jsonapi-version',
            ],
            'errors' => [
                [
                    'title' => 'Unsupported Media Type',
                    'status' => '415',
                ],
            ],
        ],
    ],
    '422' => [
        'description' => 'Unprocessable Entity',
        'example' => [
            'jsonapi' => [
                'version' => ':jsonapi-version',
            ],
            'errors' => [
                [
                    'detail' => 'Lorem Ipsum',
                    'source' => ['pointer' => '/data/attributes/lorem'],
                    'title' => 'Unprocessable Entity',
                    'status' => '422',
                ],
            ],
        ],
    ],
    '429' => [
        'description' => 'Too Many Requests',
        'example' => [
            'jsonapi' => [
                'version' => ':jsonapi-version',
            ],
            'errors' => [
                [
                    'title' => 'Too Many Requests',
                    'status' => '429',
                ],
            ],
        ],
    ],
    '4XX' => [
        'description' => 'Bad Request',
        'example' => [
            'jsonapi' => [
                'version' => ':jsonapi-version',
            ],
            'errors' => [
                [
                    'title' => 'Bad Request',
                    'status' => '400',
                ],
            ],
        ],
    ],
    '5XX' => [
        'description' => 'Server Error',
        'example' => [
            'jsonapi' => [
                'version' => ':jsonapi-version',
            ],
            'errors' => [
                [
                    'title' => 'Server Error',
                    'status' => '500',
                ],
            ],
        ],
    ],
];
