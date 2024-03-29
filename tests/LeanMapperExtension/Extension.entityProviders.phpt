<?php

declare(strict_types=1);

namespace
{
	require __DIR__ . '/bootstrap.php';
}


namespace Foo\Model
{
	class ArticleRepository extends \LeanMapper\Repository {}
}


namespace Foo\DI
{

	use Nette\DI\CompilerExtension;
	use JP\LeanMapperExtension\IEntityProvider;

	class FooExtension extends CompilerExtension implements IEntityProvider
	{
		public function loadConfiguration()
		{
			$builder = $this->getContainerBuilder();

			$builder->addDefinition($this->prefix('newsRepository'))
				->setFactory(\Foo\Model\ArticleRepository::class);
		}


		// from IEntityProvider
		function getEntityMappings()
		{
			return [
				[
					'table' => 'foo_articles',
					'primaryKey' => 'article_id',
					'entity' => \Foo\Model\Article::class,
					'repository' => \Foo\Model\ArticleRepository::class,
				],
				[
					'table' => 'news',
					'primaryKey' => 'id_news',
					'entity' => \Foo\Model\News::class,
					'repository' => \Foo\Model\NewsRepository::class,
				],
			];
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
			return [
				'broken',
			];
		}
	}
}

namespace
{

	use Tester\Assert;


	test(function () {
		$container = createContainer('readme.addons');

		$articleRepository = $container->getByType(Foo\Model\ArticleRepository::class);
		Assert::true($articleRepository instanceof Foo\Model\ArticleRepository);

		Assert::exception(function () use ($container) {
			$container->getByType(Foo\Model\NewsRepository::class);
		}, Nette\DI\MissingServiceException::class);

		$mapper = $container->getByType(LeanMapper\IMapper::class);
		Assert::true($mapper instanceof Inlm\Mappers\DynamicMapper);
		Assert::same(Foo\Model\Article::class, $mapper->getEntityClass('foo_articles'));
		Assert::same('foo_articles', $mapper->getTableByRepositoryClass(Foo\Model\ArticleRepository::class));
		Assert::same('foo_articles', $mapper->getTable(Foo\Model\Article::class));
		Assert::same('article_id', $mapper->getPrimaryKey('foo_articles'));
	});


	test(function () {
		Assert::exception(function () {
			$container = createContainer('addons.broken');
		}, InvalidArgumentException::class, 'Mappings must be array or NULL, string given.');
	});


	test(function () {
		Assert::exception(function () {
			$container = createContainer('addons.broken-mapping');
		}, InvalidArgumentException::class, 'Entity mapping must be array, string given.');
	});
}
