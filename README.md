# Sharepoint 2013 REST API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/delaneymethod/sharepoint-api.svg?style=flat-square)](https://packagist.org/packages/delaneymethod/sharepoint-api)
[![Build Status](https://img.shields.io/travis/delaneymethod/sharepoint-api/master.svg?style=flat-square)](https://travis-ci.org/delaneymethod/sharepoint-api)
[![Total Downloads](https://img.shields.io/packagist/dt/delaneymethod/sharepoint-api.svg?style=flat-square)](https://packagist.org/packages/delaneymethod/sharepoint-api)

This is a minimal PHP implementation of the [Sharepoint 2013 REST API](https://www.dropbox.com/developers/documentation/http/overview). It contains only the methods needed for our flysystem-sharepoint adapter. We are open however to PRs that add extra methods to the client.

Here are a few examples on how you can use the package:

```php
$client = new DelaneyMethod\Sharepoint\Client($refreshToken);

// Create a folder
$client->createFolder($path);
```

## Installation

You can install the package via composer:

```bash
composer require delaneymethod/sharepoint-api
```

## Usage

The first thing you need to do is get an authorisation token from Sharepoint. Sharepoint has made this very easy. You can register a new App within your Sharepoint Site that can be used to generate a client ID and Secret. You'll find more info at [Authorizing REST API calls against SharePoint Site](http://spshell.blogspot.co.uk/2015/03/sharepoint-online-o365-oauth.html). 

You can read the whole article for additional knowledge but the first step is the only step you're interested in for our flysystem-sharepoint adapter to work.

With an authorization token you can instantiate a `DelaneyMethod\Sharepoint\Client`.

```php
$client = new DelaneyMethod\Sharepoint\Client($refreshToken);
```

Look in [the source code of `DelaneyMethod\Sharepoint\Client`](https://github.com/delaneymethod/sharepoint-api/blob/master/src/Client.php) to discover the methods you can use.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email hello@delaneymethod.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
