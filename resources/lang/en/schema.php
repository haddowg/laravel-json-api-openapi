<?php

return [
    'jsonapi' => [
        'description' => 'JSON:API Specification',
        'properties' => [
            'version' => 'The version of the JSON:API specification.',
            'ext' => 'An array of URIs for all applied [extensions](https://jsonapi.org/format/#extensions).',
            'profile' => 'An array of URIs for all applied [profiles](https://jsonapi.org/format/#profiles).',
        ],
    ],
    'links' => 'A [Links Object](https://jsonapi.org/format/#document-links) containing links related to the resource or document.',
    'linkObject' => [
        'description' => 'A [Link Object](https://jsonapi.org/format/#document-links) containing a link related to the resource or document.',
        'properties' => [
            'href' => 'A string whose value is a URI-reference [RFC3986 Section 4.1](https://tools.ietf.org/html/rfc3986#section-4.1) pointing to the link’s target.',
            'rel' => "A string indicating the link's relation type. The string MUST be a [valid link relation type](https://tools.ietf.org/html/rfc8288#section-2.1).",
            'describedBy' => 'A link to a description document (e.g. OpenAPI or JSON Schema) for the link target.',
            'title' => 'A string which serves as a label for the destination of a link such that it can be used as a human-readable identifier (e.g., a menu entry).',
            'type' => 'A string indicating the media type of the link’s target.',
            'hreflang' => 'A string or an array of strings indicating the language(s) of the link’s target. An array of strings indicates that the link’s target is available in multiple languages. Each string MUST be a valid language tag [RFC5646](https://tools.ietf.org/html/rfc5646).',
        ],
    ],
    'included' => 'An array of resource objects that are related to the primary data and/or each other. See [Compound Documents](https://jsonapi.org/format/#document-compound-documents)',
    'meta' => 'A [Meta Object](https://jsonapi.org/format/#document-meta) containing non-standard meta-information.',
    'resourceId' => '[Resource Object Identifier](https://jsonapi.org/format/#document-resource-object-identification)',
    'resourceType' => '[Resource Object Type](https://jsonapi.org/format/#document-resource-object-identification)',
    'errors' => 'An array of one or more [Error Object](https://jsonapi.org/format/#error-objects) containing error information.',
    'error' => [
        'description' => 'An [Error Object](https://jsonapi.org/format/#error-objects) containing error information',
        'properties' => [
            'id' => 'A unique identifier for this particular occurrence of the problem.',
            'code' => 'An application-specific error code, expressed as a string value.',
            'detail' => 'A human-readable explanation specific to this occurrence of the problem.',
            'status' => 'The HTTP status code applicable to this problem, expressed as a string value.',
            'title' => 'The HTTP status code description applicable to this problem.',
            'source' => [
                'description' => 'Optional object pointing towards the problematic field',
                'pointer' => 'A [JSON Pointer](https://tools.ietf.org/html/rfc6901) to the associated entity in the request document [e.g. `/data` for a primary data object, or `/data/attributes/title` for a specific attribute.',
                'parameter' => 'A string indicating which query parameter caused the error.',
            ],
        ],
    ],
];
