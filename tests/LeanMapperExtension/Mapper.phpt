<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

// fallback
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper;

	Assert::same('article', $mapper->getTable('Model\Entity\Article'));
	Assert::same('Model\Entity\Article', $mapper->getEntityClass('article'));
	Assert::same('article', $mapper->getTableByRepositoryClass('Model\ArticleRepository'));
	Assert::same('id', $mapper->getPrimaryKey('article'));
});


// fallback + defaultEntityNamespace
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper('App\Model');

	Assert::same('article', $mapper->getTable('App\Model\Article'));
	Assert::same('App\Model\Article', $mapper->getEntityClass('article'));
	Assert::same('article', $mapper->getTableByRepositoryClass('App\Model\ArticleRepository'));
	Assert::same('id', $mapper->getPrimaryKey('article'));
});


// changed only primary key
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('article', NULL, NULL, 'article_id');

	Assert::same('article_id', $mapper->getPrimaryKey('article'));

	// fallback
	Assert::same('article', $mapper->getTable('Model\Entity\Article'));
	Assert::same('Model\Entity\Article', $mapper->getEntityClass('article'));
	Assert::same('article', $mapper->getTableByRepositoryClass('Model\ArticleRepository'));
});


// register only entity
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', 'Foo\Article');

	Assert::same('posts', $mapper->getTable('Foo\Article'));
	Assert::same('Foo\Article', $mapper->getEntityClass('posts'));

	// fallback
	Assert::same('article', $mapper->getTableByRepositoryClass('Foo\ArticleRepository'));
	Assert::same('id', $mapper->getPrimaryKey('posts'));
});


// register entity + repository
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', 'Foo\Article', 'Foo\ArticleRepository');

	Assert::same('posts', $mapper->getTable('Foo\Article'));
	Assert::same('Foo\Article', $mapper->getEntityClass('posts'));
	Assert::same('posts', $mapper->getTableByRepositoryClass('Foo\ArticleRepository'));

	// fallback
	Assert::same('id', $mapper->getPrimaryKey('posts'));
});


// register repository
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', NULL, 'Foo\ArticleRepository');

	Assert::same('posts', $mapper->getTableByRepositoryClass('Foo\ArticleRepository'));

	// fallback
	Assert::same('article', $mapper->getTable('Foo\Article'));
	Assert::same('Model\Entity\Posts', $mapper->getEntityClass('posts'));
	Assert::same('Model\Entity\Article', $mapper->getEntityClass('article'));
	Assert::same('id', $mapper->getPrimaryKey('posts'));
});


// register all
test(function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', 'Foo\Article', 'Foo\ArticleRepository', 'post_id');

	Assert::same('posts', $mapper->getTable('Foo\Article'));
	Assert::same('Foo\Article', $mapper->getEntityClass('posts'));
	Assert::same('posts', $mapper->getTableByRepositoryClass('Foo\ArticleRepository'));
	Assert::same('post_id', $mapper->getPrimaryKey('posts'));
});


// error - duplicated tableName
test (function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', 'Foo\Article', 'Foo\ArticleRepository');

	Assert::exception(function() use ($mapper) {
		$mapper->register('posts', 'Bar\Article', 'Bar\ArticleRepository');
	}, 'LeanMapper\Exception\InvalidStateException', 'Table \'posts\' is already registered for entity Foo\Article');
});


// error - duplicated entity class
test (function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', 'Foo\Article', 'Foo\ArticleRepository');

	Assert::exception(function() use ($mapper) {
		$mapper->register('news', 'Foo\Article', 'Bar\ArticleRepository');
	}, 'LeanMapper\Exception\InvalidStateException', 'Entity Foo\Article is already registered for table \'posts\'');
});


// error - duplicated repository class
test (function () {
	$mapper = new JP\LeanMapperExtension\Mapper;
	$mapper->register('posts', 'Foo\Article', 'Foo\ArticleRepository');

	Assert::exception(function() use ($mapper) {
		$mapper->register('news', 'Bar\Article', 'Foo\ArticleRepository');
	}, 'LeanMapper\Exception\InvalidStateException', 'Repository Foo\ArticleRepository is already registered for table \'posts\'');
});
