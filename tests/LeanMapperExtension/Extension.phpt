<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

// Basic
test(function () {
	$container = createContainer('readme.basic-usage');
	$connection = $container->getByType(LeanMapper\Connection::class);

	$config = $connection->getConfig();
	unset($config['name'], $config['result']);

	Assert::same([
		'host' => 'localhost',
		'driver' => 'fakeMysql',
		'username' => 'foo',
		'password' => 'bar',
		'database' => 'foobar',
		'lazy' => TRUE,
	], $config);

	Assert::exception(function () use ($container) {;
		$container->getByType(Dibi\Bridges\Tracy\Panel::class);
	}, Nette\DI\MissingServiceException::class);
});


// Configuration
class OwnConnection extends LeanMapper\Connection {}

test(function () {
	$container = createContainer('readme.configuration');
	$connection = $container->getByType(LeanMapper\Connection::class);
	Assert::true($connection instanceof OwnConnection);

	$config = $connection->getConfig();
	unset($config['name'], $config['result']);

	Assert::same([
		'host' => '127.0.0.1',
		'driver' => 'fakeMysql',
		'username' => 'foo',
		'password' => 'bar',
		'database' => 'foobar',
		'lazy' => TRUE,
	], $config);
});


// Entities
class NewsRepository extends LeanMapper\Repository {}

test(function () {
	$container = createContainer('readme.entities');

	$newsRepository = $container->getByType(NewsRepository::class);
	Assert::true($newsRepository instanceof NewsRepository);

	$mapper = $container->getByType(LeanMapper\IMapper::class);
	Assert::true($mapper instanceof JP\LeanMapperExtension\Mapper);

	Assert::same('UserEntity', $mapper->getEntityClass('user'));
	Assert::same('user', $mapper->getTableByRepositoryClass(Model\UserRepository::class)); // fallback
	Assert::same('user', $mapper->getTable(UserEntity::class));
	Assert::same('id', $mapper->getPrimaryKey('user'));

	Assert::same(App\Model\Article::class, $mapper->getEntityClass('articles'));
	Assert::same('articles', $mapper->getTableByRepositoryClass(Model\ArticlesRepository::class)); // fallback
	Assert::same('articles', $mapper->getTable(App\Model\Article::class));
	Assert::same('article_id', $mapper->getPrimaryKey('articles'));

	Assert::same(NewsEntity::class, $mapper->getEntityClass('newslist'));
	Assert::same('newslist', $mapper->getTableByRepositoryClass(NewsRepository::class));
	Assert::same('newslist', $mapper->getTable(NewsEntity::class));
	Assert::same('news_id', $mapper->getPrimaryKey('newslist'));
});


// Disable
test(function () {
	$container = createContainer('disable-mapper');
	Assert::exception(function () use ($container) {;
		$container->getByType(LeanMapper\IMapper::class);
	}, Nette\DI\MissingServiceException::class);
});
