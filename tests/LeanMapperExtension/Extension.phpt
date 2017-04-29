<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

// Basic
test(function () {
	$container = createContainer('readme.basic-usage');
	$connection = $container->getByType('LeanMapper\Connection');

	$config = $connection->getConfig();
	unset($config['name'], $config['result']);

	Assert::same(array(
		'host' => 'localhost',
		'driver' => 'fakeMysql',
		'username' => 'foo',
		'password' => 'bar',
		'database' => 'foobar',
		'lazy' => TRUE,
	), $config);

	Assert::exception(function () use ($container) {;
		$container->getByType('Dibi\Bridges\Tracy\Panel');
	}, 'Nette\DI\MissingServiceException');
});


// Configuration
class OwnConnection extends LeanMapper\Connection {}

test(function () {
	$container = createContainer('readme.configuration');
	$connection = $container->getByType('LeanMapper\Connection');
	Assert::true($connection instanceof OwnConnection);

	$config = $connection->getConfig();
	unset($config['name'], $config['result']);

	Assert::same(array(
		'host' => '127.0.0.1',
		'driver' => 'fakeMysql',
		'username' => 'foo',
		'password' => 'bar',
		'database' => 'foobar',
		'lazy' => TRUE,
	), $config);
});


// Entities
class NewsRepository extends LeanMapper\Repository {}

test(function () {
	$container = createContainer('readme.entities');

	$newsRepository = $container->getByType('NewsRepository');
	Assert::true($newsRepository instanceof NewsRepository);

	$mapper = $container->getByType('LeanMapper\IMapper');
	Assert::true($mapper instanceof JP\LeanMapperExtension\Mapper);

	Assert::same('UserEntity', $mapper->getEntityClass('user'));
	Assert::same('user', $mapper->getTableByRepositoryClass('Model\UserRepository')); // fallback
	Assert::same('user', $mapper->getTable('UserEntity'));
	Assert::same('id', $mapper->getPrimaryKey('user'));

	Assert::same('App\Model\Article', $mapper->getEntityClass('articles'));
	Assert::same('articles', $mapper->getTableByRepositoryClass('Model\ArticlesRepository')); // fallback
	Assert::same('articles', $mapper->getTable('App\Model\Article'));
	Assert::same('article_id', $mapper->getPrimaryKey('articles'));

	Assert::same('NewsEntity', $mapper->getEntityClass('newslist'));
	Assert::same('newslist', $mapper->getTableByRepositoryClass('NewsRepository'));
	Assert::same('newslist', $mapper->getTable('NewsEntity'));
	Assert::same('news_id', $mapper->getPrimaryKey('newslist'));
});
