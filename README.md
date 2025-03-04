# OpenApi Spec generation for Laravel JSON:API

This package provides a command to generate an OpenApi spec for a given Laravel JSON:API server.

We will attempt to infer as much as possible from the existing JSON:API server configuration and its registered resources and routes to generate the OpenApi spec without you needing to provide any additional configuration.

However there are a number of ways to customise the generated spec to better reflect your server's configuration and provide more meaningful descriptions and examples.

## Installation

You can install the package via composer:

```bash
composer require haddowg/jsonapi-openapi
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="jsonapi-openapi-config"
```

## Usage

### Generating an OpenApi Spec

To generate an OpenApi spec for a given JSON:API server, you can run the following command:
```bash
php artisan jsonapi:docs {serverName}
```

This will generate an OpenApi spec in a json format as `{serverName}.json` in the current working directory.

To modify the output filename or location or the format of the output, you can use the following options:

| Option     | shorthand | default                 | description                                         |
|------------|-----------|-------------------------|-----------------------------------------------------|
| `--output` | `-o`      | `{serverName}.{format}` | The output filepath                                 |
| `--format` | `-f`      | `json`                  | The output format. Supported values: `json`, `yaml` |


### Adding descriptions and localisation

All descriptions and summaries fields in the OpenApi spec can be added and localised using Laravel's built-in localisation features.

To start publish the package's language files:

```bash
php artisan vendor:publish --tag="jsonapi-openapi-translations"
```
You can now modify or extent these as required.

Broadly speaking translations are sourced from the first match in a list of candidate keys based on the context.

For resources specific operations and schemas:
 - `jsonapi-openapi::{serverName}.resources.{resourceType}.{key}`
 - `jsonapi-openapi::{serverName}.resource.{key}`
 - `jsonapi-openapi::resources.{resourceType}.{key}`
 - `jsonapi-openapi::resource.{key}`

For general operations and schemas:
 - `jsonapi-openapi::{serverName}.{key}`
 - `jsonapi-openapi::{key}`

This allows you to provide translations used across all resources and servers or more granular translations either for a given resource, server or combination. 

Further details including parameters that are provided for translations placeholders/replacements can be found in comments in each of the language files.

### Resource Attributes
The OpenApi schema for each resource is auto-generated based on the attribute [Fields](https://laraveljsonapi.io/4.x/schemas/attributes.html#attributes) defined in the registered Schema for that resource type, if the Schema has a [Resource](https://laraveljsonapi.io/4.x/resources/) class then the attributes will be inferred from this class.

We will attempt to generate an example resource using the eloquent Model factory for the resource type if one is available.

*All models are generated within a database transaction and rolled back after the spec generation is complete.*

For non-eloquent resources or where a eloquent factory is unavailable you can provide a ResourceFactory implementation to generate example resources. See [Resource Factories](#resource-factories) below.

This example resource will be used to generate the examples and infer the type and format of each attribute.
For attribute descriptions see [Adding descriptions and localisation](#adding-descriptions-and-localisation) above.

If the automatically generated schema for the resource is inaccurate you can provide an explicit schema via a `Attribute` attribute.
This should be applied to the `attributes` method of a resource class or the `fields` method of a schema class.

```php
use haddowg\JsonApiOpenApi\Attributes\Attribute;

#[Attribute('attributeName', new \cebe\openapi\spec\Schema([
    'type' => 'string',
    'format' => 'date-time',
]))]

public function attributes(): array
{
    return [
    ...
        Str::('attributeName'),
    ];
}
```
Optionally you can provide a third argument to the attribute to specify a unique name for this attribute and move its definition in the OpenApi document to the components/schema section and reference it.
This can be useful if you have multiple resources with the same attribute definition to avoid duplication.

### Resource Filters
The parameters for each resource filter are auto-generated based on the [Filters](https://laraveljsonapi.io/4.x/schemas/filters.html#filters) defined in the registered Schema for that resource type.

We will attempt to generate the schema for each filter based on any validation rules defined in the [Resource Query](https://laraveljsonapi.io/4.x/requests/#resource-query) or [Resource Collection Query](https://laraveljsonapi.io/4.x/requests/#resource-collection-query) class for the resource type. See [Validation](https://laraveljsonapi.io/4.x/requests/query-parameters.html#validation-rules).

Thus a rule for `filter.filterName` of `['required', 'number']` would generate a schema with `type` of `number` etc.
For filter descriptions see [Adding descriptions and localisation](#adding-descriptions-and-localisation) above.

If the automatically generated schema for the filter is inaccurate you can provide an explicit schema via a `Filter` attribute.
This should be applied to the `filters` method of the schema class.

```php
use haddowg\JsonApiOpenApi\Attributes\Filter;

#[Filter('filterName', new \cebe\openapi\spec\Schema([
    'type' => 'string',
    'format' => 'date-time',
]))]
public function filters(): array
{
    return [
    ...
        Where::make('filterName'),
    ];
}
```

### Custom or Non-Standard Actions
If you have custom actions or are using custom controller actions that do not use the provided action traits then you can document these actions by adding an Attribute to the controller method.

The following attributes are available:

#### FetchOne
`haddowg\JsonApiOpenApi\Attributes\Actions\FetchOne`
This attribute hints that the method returns a single resource as its primary data.
If the type cannot be inferred from the route then you can provide it as an argument to the attribute.

```php
    
    use haddowg\JsonApiOpenApi\Attributes\Actions\FetchOne;
    
    #[FetchOne('resourceType')]
    public function customAction(Request $request, $id)
    {
        // Your custom action code
    }
```

In the case of a single action invocable controller, the attribute should be applied to the class itself.

#### FetchMany
`haddowg\JsonApiOpenApi\Attributes\Actions\FetchMany`
This attribute hints that the method returns a collection of resources as its primary data.
If the type cannot be inferred from the route then you can provide it as an argument to the attribute.

```php
    
    use haddowg\JsonApiOpenApi\Attributes\Actions\FetchMany;
    
    #[FetchMany('resourceType')]
    public function customAction(Request $request)
    {
        // Your custom action code
    }
```

In the case of a single action invocable controller, the attribute should be applied to the class itself.

#### Create
`haddowg\JsonApiOpenApi\Attributes\Actions\Create`
This attribute hints that the method creates a new resource.
If the type cannot be inferred from the route then you can provide it as an argument to the attribute.

If the action does not return, or not only return, `201` as a successful response, you can hint the supported responses by providing an array of response codes as the second argument.
The supported response codes as per the JSON:API spec are `201`, `202` and `204`
```php
    
    use haddowg\JsonApiOpenApi\Attributes\Actions\Create;
    
    #[Create('resourceType', [201, 204])]
    public function customAction(Request $request)
    {
        // Your custom action code
    }
```
In the case of a single action invocable controller, the attribute should be applied to the class itself.

By default, the returned resource type will be assumed to be the same as that of the route and provided request body, if this action returns a different resource type you can provide this as the third argument to the attribute.

#### Update
`haddowg\JsonApiOpenApi\Attributes\Actions\Update`
If the type cannot be inferred from the route then you can provide it as an argument to the attribute.

If the action does not return, or not only return, `200` as a successful response, you can hint the supported responses by providing an array of response codes as the second argument.
The supported response codes as per the JSON:API spec are `200`, `202` and `204`
```php
    
    use haddowg\JsonApiOpenApi\Attributes\Actions\Create;
    
    #[Update('resourceType', [200, 204])]
    public function customAction(Request $request)
    {
        // Your custom action code
    }
```
In the case of a single action invocable controller, the attribute should be applied to the class itself.

By default, the returned resource type will be assumed to be the same as that of the route and provided request body, if this action returns a different resource type you can provide this as the third argument to the attribute.

### TODO

- [ ] Command to generate static redoc docs to public folder (locale option)
- [ ] config option to generate redoc docs route (include locale detection?)
- [ ] test all the things!

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Gregory Haddow](https://github.com/haddowg)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
