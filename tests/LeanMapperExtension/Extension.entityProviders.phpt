<?php
namespace {
	require __DIR__ . '/bootstrap.php';
}

namespace Foo\Model {
	class ArticleRepository extends \LeanMapper\Repository {}
}

namespace Foo\DI {
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
					'primaryKey' => 'article_id',
					'entity' => 'Foo\Model\Article',
					'repository' => 'Foo\Model\ArticleRepository',
				),
				array(
					'table' => 'news',
					'primaryKey' => 'id_news',
					'entity' => 'Foo\Model\News',
					'repository' => 'Foo\Model\NewsRepository',
					'registerRepository' => FALSE,
				),
			);
		}
	}


	class FooBrokenExtension extends CompilerExtension implements IEntityProvider
	{
		// from IEntityProvider
		function getEntityMappings()
		{
			return 'broken';
		}
	}


	class FooBrokenMappingExtension extends CompilerExtension implements IEntityProvider
	{
		// from IEntityProvider
		function getEntityMappings()
		{
			return array(
				'broken',
			);
		}
	}
}

namespace {
	use Tester\Assert;

	test(function () {
		$container = createContainer('readme.addons');

		$articleRepository = $container->getByType('Foo\Model\ArticleRepository');
		Assert::true($articleRepository instanceof Foo\Model\ArticleRepository);

		Assert::exception(function () use ($container) {
			$container->getByType('Foo\Model\NewsRepository');
		}, 'Nette\DI\MissingServiceException');

		$mapper = $container->getByType('LeanMapper\IMapper');
		Assert::true($mapper instanceof JP\LeanMapperExtension\Mapper);
		Assert::same('Foo\Model\Article', $mapper->getEntityClass('foo_articles'));
		Assert::same('foo_articles', $mapper->getTableByRepositoryClass('Foo\Model\ArticleRepository'));
		Assert::same('foo_articles', $mapper->getTable('Foo\Model\Article'));
		Assert::same('article_id', $mapper->getPrimaryKey('foo_articles'));
	});


	test(function () {
		Assert::exception(function () {
			$container = createContainer('addons.broken');
		}, 'InvalidArgumentException', 'Mappings must be array or NULL, string given.');
	});


	test(function () {
		Assert::exception(function () {
			$container = createContainer('addons.broken-mapping');
		}, 'InvalidArgumentException', 'Entity mapping must be array or NULL, string given.');
	});
}
