Auth Component for Orchestra Platform
==============

Auth Component extends the functionality of Illuminate\Auth with the extra functionality to retrieve users' role. This is important when we want to use Orchestra\Acl to manage application Access Control List (ACL).

[![Latest Stable Version](https://poser.pugx.org/orchestra/auth/v/stable.png)](https://packagist.org/packages/orchestra/auth)
[![Total Downloads](https://poser.pugx.org/orchestra/auth/downloads.png)](https://packagist.org/packages/orchestra/auth)
[![Build Status](https://travis-ci.org/orchestral/auth.svg?branch=master)](https://travis-ci.org/orchestral/auth)
[![Coverage Status](https://coveralls.io/repos/orchestral/auth/badge.png?branch=master)](https://coveralls.io/r/orchestral/auth?branch=master)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/orchestral/auth/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/orchestral/auth/)

## Quick Installation

To install through composer, simply put the following in your `composer.json` file:

```json
{
	"require": {
		"orchestra/auth": "3.0.*"
	}
}
```

Next replace `Illuminate\Auth\AuthServiceProvider` with the following service provider in `app/config/app.php`.

```php
'providers' => array(

	// ...

	'Orchestra\Auth\AuthServiceProvider',
	'Orchestra\Authorization\AuthorizationServiceProvider',
	'Orchestra\Memory\MemoryServiceProvider',

	'Orchestra\Auth\CommandServiceProvider',
	'Orchestra\Memory\CommandServiceProvider',
),
```

> `CommandServiceProvider` are optional, useful for interaction using the Artisan command line tool.

### Aliases

To make development easier, you could add `Orchestra\Support\Facades\Acl` alias for easier reference:

```php
'aliases' => array(

	'Orchestra\Acl' => 'Orchestra\Support\Facades\ACL',

),
```

## Resources

* [Documentation](http://orchestraplatform.com/docs/latest/components/auth)
* [Change Log](http://orchestraplatform.com/docs/latest/components/auth/changes#v3-0)
