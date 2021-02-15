
LeanMapper-extension
====================

[![Tests Status](https://github.com/janpecha/leanmapper-extension/workflows/Tests/badge.svg)](https://github.com/janpecha/leanmapper-extension/actions)

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
			repository: RepositoryClass # repository is auto-registred in DI container
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
				'repository' => Foo\Model\ArticleRepository::class, # repository is auto-registred in DI container, see option 'registerRepository'
				'registerRepository' => TRUE, // optional
			),
			// ...
		);
	}
}
```

------------------------------

License: [New BSD License](license.md)
<br>Author: Jan Pecha, http://janpecha.iunas.cz/
