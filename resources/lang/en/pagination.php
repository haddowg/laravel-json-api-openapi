<?php
/**
 * This file is used to define and localise descriptions for common pagination parameters in the spec.
 */
return [
    // Page based pagination
    'page' => [
        'parameters' => [
            'pageKey' => 'The page number for paginated results',
            'perPageKey' => 'The number of resources to be shown per page',
        ],
        'meta' => [
            'description' => 'Pagination meta information',
            'currentPage' => 'The current page number',
            'from' => 'The result number of the first resource in the page',
            'lastPage' => 'The page number of the last available page',
            'perPage' => 'The number of resources to be shown per page',
            'to' => 'The result number of the last resource in the page',
            'total' => 'The total number or resources being paginated',
        ],
        'links' => [
            'first' => 'The first page of results',
            'last' => 'The last page of results',
            'prev' => 'The previous page of results',
            'next' => 'The next page of results',
        ],
    ],
    // Cursor based pagination
    'cursor' => [
        'parameters' => [
            'limit' => 'The number of resources to return per page of results',
            'before' => 'Show resources before the provided cursor',
            'after' => 'Show resources after the provided cursor',
        ],
        'meta' => [
            'description' => 'Pagination meta information',
            'from' => 'The cursor to the first resource in the page',
            'hasMore' => 'If there are more resources available based on the current cursor',
            'perPage' => 'The number of resources to be shown per page',
            'to' => 'The cursor to the last resource in the page',
            'total' => 'The total number or resources being paginated',
        ],
        'links' => [
            'first' => 'The first page of results',
            'prev' => 'The previous page of results',
            'next' => 'The next page of results',
        ],
    ],
];
