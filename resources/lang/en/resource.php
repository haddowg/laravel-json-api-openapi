<?php
/**
 * This file is used to define and localise descriptions and summaries in the spec for any/all resource type.
 *
 * To customize these keys for specific resources or define resource specific keys such as attributes see the `resources.php` file in this vendor directory.
 *
 * These translations are used for every server so you do not need to duplicate for common resources.
 * If however you need to override a translation for a specific server, you can do so by adding
 * any keys within a `resource` key in a `{serverName}.php` language file in this vendor directory.
 * e.g. `resources/lang/vendor/jsonapi-openapi/en/v1.php`
 * ```
 * return [
 *  'actions' => [
 *     'viewingAny' => [
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
    'actions' => [
        'viewingAny' => [
            'description' => 'Get all :Resource-type resources',
            'summary' => 'Get all :Resource-type',
            'responses' => [
                '200' => [
                    'description' => '[OK](https://jsonapi.org/format/#fetching-resources-responses-200)',
                ],
                '404' => [
                    'description' => '[Not Found](https://jsonapi.org/format/#fetching-resources-responses-404)',
                ],
            ],
        ],
        'viewingOne' => [
            'description' => 'Show one :Resource-type-singular resource by ID',
            'summary' => 'Show one :Resource-type-singular',
            'responses' => [
                '200' => [
                    'description' => '[OK](https://jsonapi.org/format/#fetching-resources-responses-200)',
                ],
                '404' => [
                    'description' => '[Not Found](https://jsonapi.org/format/#fetching-resources-responses-404)',
                ],
            ],
        ],
        'creating' => [
            'description' => 'Create a new :Resource-type-singular resource',
            'summary' => 'Create a :Resource-type-singular',
            'responses' => [
                '201' => [
                    'description' => '[Created](https://jsonapi.org/format/#crud-creating-responses-201)',
                ],
                '403' => [
                    'description' => '[Forbidden](https://jsonapi.org/format/#crud-creating-responses-403)',
                ],
                '404' => [
                    'description' => '[Not Found](https://jsonapi.org/format/#crud-creating-responses-404)',
                ],
                '409' => [
                    'description' => '[Conflict](https://jsonapi.org/format/#crud-creating-responses-409)',
                ],
            ],
        ],
        'updating' => [
            'description' => 'Update a :Resource-type-singular resource by ID',
            'summary' => 'Update a :Resource-type-singular',
            'responses' => [
                '200' => [
                    'description' => '[OK](https://jsonapi.org/format/#crud-updating-responses-200)',
                ],
                '403' => [
                    'description' => '[Forbidden](https://jsonapi.org/format/#crud-updating-responses-403)',
                ],
                '404' => [
                    'description' => '[Not Found](https://jsonapi.org/format/#crud-updating-responses-404)',
                ],
                '409' => [
                    'description' => '[Conflict](https://jsonapi.org/format/#crud-updating-responses-409)',
                ],
            ],
        ],
        'deleting' => [
            'description' => 'Delete a :Resource-type-singular resource by ID',
            'summary' => 'Delete a :Resource-type-singular',
            'responses' => [
                '204' => [
                    'description' => '[No Content](https://jsonapi.org/format/#crud-deleting-responses-204)',
                ],
                '404' => [
                    'description' => '[Not Found](https://jsonapi.org/format/#crud-deleting-responses-404)',
                ],
            ],
        ],
        'viewingRelated' => [
            'description' => 'Show the :Relation (:Related-resource-type) of a :Parent-resource-type-singular resource by ID',
            'summary' => 'Show the :Relation of a :Parent-resource-type-singular',
            'responses' => [
                '200' => [
                    'description' => '[OK](https://jsonapi.org/format/#fetching-resources-responses-200)',
                ],
                '404' => [
                    'description' => '[Not Found](https://jsonapi.org/format/#fetching-resources-responses-404)',
                ],
            ],
        ],
        'viewingRelationship' => [
            'description' => 'Show the :Relation (:Related-resource-type) relationship resource linkage of a :Parent-resource-type-singular resource by ID',
            'summary' => 'Show the :Relation relationship of a :Parent-resource-type-singular',
            'responses' => [
                '200' => [
                    'description' => '[OK](https://jsonapi.org/format/#fetching-relationships-responses-200)',
                ],
                '404' => [
                    'description' => '[Not Found](https://jsonapi.org/format/#fetching-relationships-responses-404)',
                ],
            ],
        ],
        'attachingRelationship' => [
            'description' => 'Attach :Related-resource-type to the :Relation relationship of a :Parent-resource-type-singular resource by ID',
            'summary' => 'Attach :Related-resource-type to the :Relation relationship of a :Parent-resource-type-singular',
            'responses' => [
                '204' => [
                    'description' => '[No Content](https://jsonapi.org/format/#crud-updating-relationship-responses-204)',
                ],
                '403' => [
                    'description' => '[Forbidden](https://jsonapi.org/format/#crud-updating-relationship-responses-403)',
                ],
            ],
        ],
        'detachingRelationship' => [
            'description' => 'Detach :Related-resource-type from the :Relation relationship of a :Parent-resource-type-singular resource by ID',
            'summary' => 'Detach :Related-resource-type from the :Relation relationship of a :Parent-resource-type-singular',
            'responses' => [
                '204' => [
                    'description' => '[No Content](https://jsonapi.org/format/#crud-updating-relationship-responses-204)',
                ],
                '403' => [
                    'description' => '[Forbidden](https://jsonapi.org/format/#crud-updating-relationship-responses-403)',
                ],
            ],
        ],
        'updatingRelationship' => [
            'description' => 'Update the :Relation (:related-resource-type) relationship of a :Parent-resource-type-singular resource by ID',
            'summary' => 'Update the :Relation relationship of a :Parent-resource-type-singular',
            'responses' => [
                '204' => [
                    'description' => '[No Content](https://jsonapi.org/format/#crud-updating-relationship-responses-204)',
                ],
                '403' => [
                    'description' => '[Forbidden](https://jsonapi.org/format/#crud-updating-relationship-responses-403)',
                ],
            ],
        ],
    ],
    'data' => [
        'one' => 'Single :Resource-type-singular resource',
        'many' => 'Collection of :Resource-type-singular resources',
        'store' => 'The :Resource-type-singular resource to be created',
        'update' => 'The :Resource-type-singular resource to be updated',
    ],
    'parameters' => [
        'resource-id' => 'A :Resource-type-singular Resource Identifier',
        'filter' => 'Filters the :Resource-type',
    ],
    'schema' => [
        'title' => ':Resource-type-singular resource',
        'attributes-description' => '[Resource Object Attributes](https://jsonapi.org/format/#document-resource-object-attributes)',
        'attribute' => 'The :Attribute of the :Resource-type-singular',
        'relationships-description' => '[Resource Object Relationships](https://jsonapi.org/format/#document-resource-object-relationships)',
        'relationship' => [
            'description' => [
                'toMany' => 'The :relation (:Related-resource-type) of the :Parent-resource-type-singular',
                'toOne' => 'The :relation (:Related-resource-type) of the :Parent-resource-type-singular',
            ],
            'data' => [
                'many' => 'Array of related :Related-resource-type [Resource Object Linkage](https://jsonapi.org/format/#document-resource-object-linkage)',
                'one' => 'The related :Related-resource-type [Resource Object Linkage](https://jsonapi.org/format/#document-resource-object-linkage)',
            ],
            'links-description' => 'A [Links Object](https://jsonapi.org/format/#document-links) containing links related to the relationship.',
            'links' => [
                'self' => 'a link for the :relation relationship itself (A [Relationship Link)(https://jsonapi.org/format/1.1/#fetching-relationships))',
                'related' => [
                    'toMany' => 'a link to retrieved the related :Related-resource-type from the :Parent-resource-type-singular (A [Related Resource Link](https://jsonapi.org/format/1.1/#document-resource-object-related-resource-links))',
                    'toOne' => 'a link to retrieved the related :Related-resource-type from the :Parent-resource-type-singular (A [Related Resource Link](https://jsonapi.org/format/1.1/#document-resource-object-related-resource-links))',
                ],
            ],
        ],
        'links-description' => 'A [Links Object](https://jsonapi.org/format/#document-links) containing links related to the resource.',
        'meta-description' => 'A [Meta Object](https://jsonapi.org/format/#document-meta) containing non-standard meta-information.',
    ],
];
