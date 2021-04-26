
LeanMapper-extension
====================

[![Tests Status](https://github.com/janpecha/leanmapper-extension/workflows/Tests/badge.svg)](https://github.com/janpecha/leanmapper-extension/actions)

[Lean Mapper](http://leanmapper.com/) extension for [Nette](https://nette.org).


Support Me
----------

Do you like LeanMapper-extension? Are you looking forward to the **new features**?

<a href="https://www.paypal.com/donate?hosted_button_id=BWR5RJCDLY7SG"><img src="https://buymecoffee.intm.org/img/janpecha-paypal-donate@2x.png" alt="PayPal or credit/debit card" width="254" height="248"></a>

<img src="https://buymecoffee.intm.org/img/bitcoin@2x.png" alt="Bitcoin" height="32"> `bc1qrq9egf99a6z3576twggrp6uv5td5r3pq0j4awe`

Thank you!


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
			[
				'baseEntity' => Model\Entity\Client::class,
				'type' => 'company',
				'entity' => Model\Entity\ClientCompany::class,
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
			[
				'entity' => \Model\Entity\OrderItem::class,
				'field' => 'currency',
				'fromDbValue' => [static::class, 'currencyFromDb'],
				'toDbValue' => [static::class, 'currencyToDb'],
			],
			// ...
		];
	}


	function getRowMultiValueMappings()
	{
		return [
			[
				'entity' => \Model\Entity\OrderItem::class,
				'field' => 'price',
				'fromDbValue' => [static::class, 'priceFromDb'],
				'toDbValue' => [static::class, 'priceToDb'],
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
