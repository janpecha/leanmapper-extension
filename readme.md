LeanMapper-extension
====================

[![Build Status](https://github.com/janpecha/leanmapper-extension/workflows/Build/badge.svg)](https://github.com/janpecha/leanmapper-extension/actions)
[![Downloads this Month](https://img.shields.io/packagist/dm/janpecha/leanmapper-extension.svg)](https://packagist.org/packages/janpecha/leanmapper-extension)
[![Latest Stable Version](https://poser.pugx.org/janpecha/leanmapper-extension/v/stable)](https://github.com/janpecha/leanmapper-extension/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/janpecha/leanmapper-extension/blob/master/license.md)

[Lean Mapper](http://leanmapper.com/) extension for [Nette](https://nette.org).

<a href="https://www.janpecha.cz/donate/"><img src="https://buymecoffee.intm.org/img/donate-banner.v1.svg" alt="Donate" height="100"></a>


Installation
------------

[Download a latest package](https://github.com/janpecha/leanmapper-extension/releases) or use [Composer](http://getcomposer.org/):

```
composer require janpecha/leanmapper-extension
```

Extension requires:
* PHP 5.6 or later
* Nette 2.4 or later
* LeanMapper 3.0 or later


Usage
-----

``` neon
extensions:
	leanmapper: JP\LeanMapperExtension\LeanMapperExtension


leanmapper:
	# database connection
	username: ...
	password: ...
	database: ...
```


Configuration
-------------

### Database connection

``` neon
leanmapper:
	# required
	username: ...
	password: ...
	database: ...

	# optional
	connection: LeanMapper\Connection
	host: localhost
	driver: mysqli
	lazy: true
	profiler: ...    # on|off or NULL => enabled in debug mode, disabled in production mode
	charset: utf8mb
```


### Entities

``` neon
leanmapper:
	entityFactory: LeanMapper\DefaultEntityFactory
	entityMapping:
		table: EntityClass

		table:
			entity: EntityClass
			repository: RepositoryClass # only mapping, you need manually register repository to DI
			primaryKey: table_primary_key

		articles:
			entity: App\Model\Article
			primaryKey: article_id
```


### Mapper

``` neon
leanmapper:
	mapper: true # bool
	defaultEntityNamespace: 'Model\Entity'
	nameMapping: camelcase # default | camelcase | underscore
	prefix: null
```


Support for addons
------------------

``` php
use Nette\DI\CompilerExtension;
use JP\LeanMapperExtension\IEntityProvider;

class FooExtension extends CompilerExtension implements IEntityProvider
{
	// from IEntityProvider
	function getEntityMappings()
	{
		return array(
			array(
				'table' => 'foo_articles',
				'primaryKey' => 'id',
				'entity' => Foo\Model\Article::class,
				'repository' => Foo\Model\ArticleRepository::class, # only mapping, you need manually register repository to DI
			),
			// ...
		);
	}
}
```

STI mapping

``` php
use Nette\DI\CompilerExtension;
use JP\LeanMapperExtension\IStiMappingProvider;

class FooExtension extends CompilerExtension implements IStiMappingProvider
{
	function getStiMappings()
	{
		return [
			Model\Entity\Client::class => [ // base entity
				// type => target entity
				'company' => Model\Entity\ClientCompany::class,
			],
			// ...
		];
	}


	function getStiTypeFields()
	{
		return [
			Model\Entity\Client::class => 'clientType',
		];
	}
}
```

Row mapping

``` php
use Nette\DI\CompilerExtension;
use JP\LeanMapperExtension\IRowMappingProvider;

class FooExtension extends CompilerExtension implements IRowMappingProvider
{
	function getRowFieldMappings()
	{
		return [
			\Model\Entity\OrderItem::class => [
				'currency' => [
					'fromDbValue' => [static::class, 'currencyFromDb'],
					'toDbValue' => [static::class, 'currencyToDb'],
				]
			],
			// ...
		];
	}


	function getRowMultiValueMappings()
	{
		return [
			\Model\Entity\OrderItem::class => [
				'price' => [
					'fromDbValue' => [static::class, 'priceFromDb'],
					'toDbValue' => [static::class, 'priceToDb'],
				],
			],
		];
	}


	static function currencyFromDb($value)
	{
		return strtoupper($value);
	}


	static function currencyToDb($value)
	{
		return strtolower($value);
	}


	static function priceFromDb(array $values)
	{
		return [$values['price'], $values['currency']];
	}


	static function priceToDb($value)
	{
		return [
			'price' => $value[0],
		];
	}
}
```

------------------------------

License: [New BSD License](license.md)
<br>Author: Jan Pecha, http://janpecha.iunas.cz/
