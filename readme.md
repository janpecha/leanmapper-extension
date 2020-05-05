
LeanMapper-extension
====================

[![Build Status](https://travis-ci.org/janpecha/leanmapper-extension.svg?branch=master)](https://travis-ci.org/janpecha/leanmapper-extension)

[Lean Mapper](http://leanmapper.com/) extension for [Nette](https://nette.org).

---

**Do you like this project? Please consider its support. Thank you!**

<a href="https://www.patreon.com/bePatron?u=9680759"><img src="https://c5.patreon.com/external/logo/become_a_patron_button.png" alt="Become a Patron!" height="35"></a>
<a href="https://www.paypal.me/janpecha/5eur"><img src="https://buymecoffee.intm.org/img/button-paypal-white.png" alt="Buy me a coffee" height="35"></a>

---


Installation
------------

[Download a latest package](https://github.com/janpecha/leanmapper-extension/releases) or use [Composer](http://getcomposer.org/):

```
composer require janpecha/leanmapper-extension
```

Extension requires:
* PHP 5.6 or later
* Nette 2.2 or later
* LeanMapper 2.2 or later


Usage
-----

``` yaml
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

``` yaml
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
	charset: ...
```


### Entities

``` yaml
leanmapper:
	entityFactory: LeanMapper\DefaultEntityFactory
	entities:
		table: EntityClass

		table:
			entity: EntityClass
			repository: RepositoryClass # repository is auto-registred in DI container
			primaryKey: table_primary_key

		articles:
			entity: App\Model\Article
			primaryKey: article_id
```


### Mapper

``` yaml
leanmapper:
	mapper: JP\LeanMapperExtension\Mapper
	defaultEntityNamespace: 'Model\Entity'
```


Support for addons
------------------

``` php
<?php
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
				'entity' => 'Foo\Model\Article',
				'repository' => 'Foo\Model\ArticleRepository', # repository is auto-registred in DI container, see option 'registerRepository'
				'registerRepository' => TRUE, // optional
			),
			// ...
		);
	}
}
?>
```


Mapper (for experts)
------

``` php
<?php
$mapper = JP\LeanMapperExtension\Mapper($defaultEntityNamespace = NULL);

// register entity
$mapper->register($tableName, $entity = NULL, $repository = NULL, $primaryKey = NULL);
?>
```

------------------------------

License: [New BSD License](license.md)
<br>Author: Jan Pecha, http://janpecha.iunas.cz/
