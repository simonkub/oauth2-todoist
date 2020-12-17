# Todoist Provider for OAuth 2.0 Client

[![Latest Version on Packagist](https://img.shields.io/packagist/v/simonkub/oauth2-todoist.svg?style=flat-square)](https://packagist.org/packages/simonkub/oauth2-todoist)
![Tests](https://github.com/simonkub/oauth2-todoist/workflows/Tests/badge.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/simonkub/oauth2-todoist.svg?style=flat-square)](https://packagist.org/packages/simonkub/oauth2-todoist)

Todoist Sync API OAuth 2.0 support for the PHP League's OAuth 2.0 Client.

## Installation

You can install the package via composer:

```bash
composer require simonkub/oauth2-todoist
```

## Usage

```php
// configure and create client
$client = new Simonkub\OAuth2\Client\Todoist([
    "clientId" => "CLIENT_ID",
    "clientSecret" => "CLIENT_SECRET",
    "redirectUri" => "REDIRECT_URI"
]);

// optionally set client scopes, defaults to "data:read"
$client->setDefaultScopes(["data:read"]);

// redirect to todoist login page
$loginPageUri = $client->getAuthorizationUrl();

// after login and beeing redirected back to your application
// read authorization code from request
$authorizationCode = $_GET['code'];

// exchange authorization code for token
$token = $client->getAccessToken("authorization_code", [
    "code" => $authorizationCode
]);

// read single resource
$response = $client->readResource("projects", $token);

// read multiple resources
$response = $client->readResources(["projects", "labels"], $token);

// write resource
$commands = [
    "type" => "item_add",
    "uuid" => uniqid(),
    "temp_id" => uniqid(),
    "args" => [
        "project_id" => "foo",
        "content" => "bar"
    ]
];
$response = $client->writeResources($commands, $token);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Simon Kubiak](https://github.com/simonkub)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
