# OpenApi Spec generation for Laravel JSON:API

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

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

```bash
php artisan jsonapi:docs {serverName}
```

### TODO

- [ ] Make translation of attribute descriptions recursive
- [ ] Command to generate/publish language file stubs
- [ ] Document localisation with lang files
- [ ] Document customising via attributes on resource/schema/controller
- [ ] Document how to document custom actions]
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
