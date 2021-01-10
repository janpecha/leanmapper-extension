<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

// fallback
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper;

	Assert::same('article', $mapper->getTable(Model\Entity\Article::class));
	Assert::same(Model\Entity\Article::class, $mapper->getEntityClass('article'));
	Assert::same('article', $mapper->getTableByRepositoryClass(Model\ArticleRepository::class));
	Assert::same('id', $mapper->getPrimaryKey('article'));
});


// fallback + defaultEntityNamespace
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper(App\Model::class);

	Assert::same('article', $mapper->getTable(App\Model\Article::class));
	Assert::same(App\Model\Article::class, $mapper->getEntityClass('article'));
	Assert::same('article', $mapper->getTableByRepositoryClass(App\Model\ArticleRepository::class));
	Assert::same('id', $mapper->getPrimaryKey('article'));
});


// changed only primary key
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('article', NULL, NULL, 'article_id');

	Assert::same('article_id', $mapper->getPrimaryKey('article'));

	// fallback
	Assert::same('article', $mapper->getTable(Model\Entity\Article::class));
	Assert::same(Model\Entity\Article::class, $mapper->getEntityClass('article'));
	Assert::same('article', $mapper->getTableByRepositoryClass(Model\ArticleRepository::class));
});


// register only entity
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', Foo\Article::class);

	Assert::same('posts', $mapper->getTable(Foo\Article::class));
	Assert::same(Foo\Article::class, $mapper->getEntityClass('posts'));

	// fallback
	Assert::same('article', $mapper->getTableByRepositoryClass(Foo\ArticleRepository::class));
	Assert::same('id', $mapper->getPrimaryKey('posts'));
});


// register entity + repository
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', Foo\Article::class, Foo\ArticleRepository::class);

	Assert::same('posts', $mapper->getTable(Foo\Article::class));
	Assert::same(Foo\Article::class, $mapper->getEntityClass('posts'));
	Assert::same('posts', $mapper->getTableByRepositoryClass(Foo\ArticleRepository::class));

	// fallback
	Assert::same('id', $mapper->getPrimaryKey('posts'));
});


// register repository
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', NULL, Foo\ArticleRepository::class);

	Assert::same('posts', $mapper->getTableByRepositoryClass(Foo\ArticleRepository::class));

	// fallback
	Assert::same('article', $mapper->getTable(Foo\Article::class));
	Assert::same(Model\Entity\Posts::class, $mapper->getEntityClass('posts'));
	Assert::same(Model\Entity\Article::class, $mapper->getEntityClass('article'));
	Assert::same('id', $mapper->getPrimaryKey('posts'));
});


// register all
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', Foo\Article::class, Foo\ArticleRepository::class, 'post_id');

	Assert::same('posts', $mapper->getTable(Foo\Article::class));
	Assert::same(Foo\Article::class, $mapper->getEntityClass('posts'));
	Assert::same('posts', $mapper->getTableByRepositoryClass(Foo\ArticleRepository::class));
	Assert::same('post_id', $mapper->getPrimaryKey('posts'));
});


// error - duplicated tableName
test (function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', Foo\Article::class, Foo\ArticleRepository::class);

	Assert::exception(function() use ($mapper) {
		$mapper->register('posts', Bar\Article::class, Bar\ArticleRepository::class);
	}, LeanMapper\Exception\InvalidStateException::class, 'Table \'posts\' is already registered for entity ' . Foo\Article::class);
});


// error - duplicated entity class
test (function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', Foo\Article::class, Foo\ArticleRepository::class);

	Assert::exception(function() use ($mapper) {
		$mapper->register('news', Foo\Article::class, Bar\ArticleRepository::class);
	}, LeanMapper\Exception\InvalidStateException::class, 'Entity ' . Foo\Article::class . ' is already registered for table \'posts\'');
});


// error - duplicated repository class
test (function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', Foo\Article::class, Foo\ArticleRepository::class);

	Assert::exception(function() use ($mapper) {
		$mapper->register('news', Bar\Article::class, Foo\ArticleRepository::class);
	}, LeanMapper\Exception\InvalidStateException::class, 'Repository ' . Foo\ArticleRepository::class . ' is already registered for table \'posts\'');
});
