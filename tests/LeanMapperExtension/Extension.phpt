<?php

declare(strict_types=1);

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
		'charset' => 'utf8mb4',
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
		'charset' => 'utf8mb4',
	], $config);
});


// Entities
class NewsRepository extends LeanMapper\Repository {}

test(function () {
	$container = createContainer('readme.entities');

	$connection = $container->getService('leanmapper.connection');
	Assert::true($connection instanceof \LeanMapper\Connection);

	$entityFactory = $container->getService('leanmapper.entityFactory');
	Assert::true($entityFactory instanceof \LeanMapper\DefaultEntityFactory);

	$newsRepository = $container->getByType(NewsRepository::class);
	Assert::true($newsRepository instanceof NewsRepository);

	$mapper = $container->getByType(LeanMapper\IMapper::class);
	Assert::true($mapper instanceof \Inlm\Mappers\DynamicMapper);

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

// name mapping - default
test(function () {
	$container = createContainer('nameMapping.default');

	$mapper = $container->getByType(LeanMapper\IMapper::class);
	Assert::true($mapper instanceof \Inlm\Mappers\DefaultMapper);
});

// name mapping - camelcase
test(function () {
	$container = createContainer('nameMapping.camelcase');

	$mapper = $container->getByType(LeanMapper\IMapper::class);
	Assert::true($mapper instanceof \Inlm\Mappers\CamelCaseMapper);
});

// name mapping - underscore
test(function () {
	$container = createContainer('nameMapping.underscore');

	$mapper = $container->getByType(LeanMapper\IMapper::class);
	Assert::true($mapper instanceof \Inlm\Mappers\UnderScoreMapper);
});

// Prefix
test(function () {
	$container = createContainer('prefix');

	$mapper = $container->getByType(LeanMapper\IMapper::class);
	Assert::true($mapper instanceof \Inlm\Mappers\PrefixMapper);

	Assert::same('prefix_user', $mapper->getTable(User::class));
});


// Disable
test(function () {
	$container = createContainer('disable-services');

	Assert::exception(function () use ($container) {;
		$container->getByType(LeanMapper\Connection::class);
	}, Nette\DI\MissingServiceException::class);

	Assert::exception(function () use ($container) {;
		$container->getByType(LeanMapper\IMapper::class);
	}, Nette\DI\MissingServiceException::class);

	Assert::exception(function () use ($container) {;
		$container->getByType(LeanMapper\IEntityFactory::class);
	}, Nette\DI\MissingServiceException::class);
});
