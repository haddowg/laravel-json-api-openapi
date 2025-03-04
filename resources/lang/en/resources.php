<?php
/**
 * This file is used to define and localise descriptions and summaries in the spec for each resource type.
 *
 * To define common translations for all resources see the `resource.php` file in this vendor directory.
 *
 * These translations are used for every server so you do not need to duplicate for common resources.
 * If however you need to override a translation for a specific server, you can do so by adding
 * any keys within a `resources` key in a `{serverName}.php` language file in this vendor directory.
 * e.g. `resources/lang/vendor/jsonapi-openapi/en/v1.php`
 * ```
 * return [
 *  'resources' => [
 *     'my-resources' => [
 *          ...
 *     ],
 *  ],
 * ];
 * ```
 *
 * The following common parameters are provided when translating a key:
 *
 * - :app - The name of the application (e.g. config('app.name'))
 * - :server - The jsonapi server name (e.g. 'v1')
 * - :jsonapi-version - The JSON:API specification version (e.g. '1.0')
 * - :resource-type - The resource type (e.g. 'my-resources')
 * - :resource-type-singular - The singular form of the resource type (e.g. 'my-resource')
 *
 * When translating for a relationship operation the following additional parameters are provided:
 *
 * - :relation - The relationship name (e.g. 'author')
 * - :related-resource-type - The related resource type (e.g. 'user') this will automatically be the singular or plural form depending on the relationship type.
 * - :parent-resource-type - The parent resource type (e.g. 'posts')
 * - :parent-resource-type-singular - The singular form of the parent resource type (e.g. 'post')
 *
 * These can be used via placeholders in the translations as per usual. See [Replacing Placeholders](https://laravel.com/docs/11.x/localization#replacing-parameters-in-translation-strings) in the Laravel docs.
 * Some additional parameters may be provided depending on the context, these will be documented below.
 */
return [
    /**
     * Top level keys should be a resource type
     * You only need to define the keys you want to override or add, others can be removed and will be sourced from the package defaults.
     */
    'my-resources' => [
        /**
         * You can override the description and summary for each resource action (operation)
         * and for each response status code
         */
        'actions' => [
            /**
             * The actions name, these correspond to default controller methods and action traits.
             */
            'index' => [
                'description' => 'Fetch all My Resources',
                'summary' => 'Fetch all My Resources',
                'responses' => [
                    '200' => [
                        'description' => 'my custom 200 response description',
                    ],
                    '404' => [
                        'description' => 'my custom 404 response description',
                    ],
                ],
            ],
            'show' => [
                // ...
            ],
            // ...
            /**
             * You can also add any custom actions
             */
            'my-custom-action' => [
                'description' => 'My custom action description',
                'summary' => 'My custom action summary',
            ],
            // ...
        ],
        'parameters' => [
            /**
             * You can override the default parameter descriptions
             */
            'resource-id' => 'Custom id path parameter description for my resource',
            'sort' => 'Sort the my resources by one or more fields',
            'filter' => 'Filter the my resources by one or more fields',
            /**
             * And provide descriptions for any filters defined for this resource
             */
            'filters' => [
                'filter1' => 'Filter 1 description',
                'filter2' => 'Filter 2 description',
                // ...
            ],
        ],
        'pagination' => [
            /**
             * You can override the default descriptions for pagination parameters and meta
             * the defaults can be found in the `pagination.php` file in this vendor directory
             */
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
