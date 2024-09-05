# Laravel Models are now Uber Flexy!
Laravel FlexyField is useful package for dynamic model fields.

## Installation

You can install the package via composer:

```bash
composer require aurora-web-software-team/flexyfield
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="flexyfield-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="flexyfield-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="flexyfield-views"
```

## Usage

```php
$flexyField = new AuroraWebSoftware\FlexyField();
echo $flexyField->echoPhrase('Hello, AuroraWebSoftware!');
```

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

- [Aurora Web Software Team](https://github.com/Aurora Web Software Team)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
