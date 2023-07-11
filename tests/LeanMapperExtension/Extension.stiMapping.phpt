<?php

declare(strict_types=1);

namespace
{
	require __DIR__ . '/bootstrap.php';
}


namespace Foo\DI
{

	use Nette\DI\CompilerExtension;
	use JP\LeanMapperExtension\IStiMappingProvider;

	class FooExtension extends CompilerExtension implements IStiMappingProvider
	{
		function getStiMappings()
		{
			return [
				// old syntax
				[
					'baseEntity' => \Model\Entity\Client::class,
					'type' => 'company',
					'entity' => \Model\Entity\ClientCompany::class,
				],
				// new syntax
				\Model\Entity\Client::class => [
					'individual' => \Model\Entity\ClientIndividual::class,
				]
				// ...
			];
		}


		function getStiTypeFields()
		{
			return [
				\Model\Entity\Client::class => 'clientType',
			];
		}
	}
}

namespace
{

	use Tester\Assert;


	test(function () {
		$container = createContainer('sti');

		$mapper = $container->getByType(LeanMapper\IMapper::class);
		Assert::true($mapper instanceof Inlm\Mappers\StiMapper);

		Assert::same(Model\Entity\Client::class, $mapper->getEntityClass('client'));

		$row = LeanMapper\Result::createDetachedInstance()->getRow();
		$row->clientType = 'company';
		Assert::same(Model\Entity\ClientCompany::class, $mapper->getEntityClass('client', $row));

		Assert::same('client', $mapper->getTable(Model\Entity\Client::class));
		Assert::same('client', $mapper->getTable(Model\Entity\ClientCompany::class));
	});


	test(function () {
		$container = createContainer('sti');

		$mapper = $container->getByType(LeanMapper\IMapper::class);
		Assert::true($mapper instanceof Inlm\Mappers\StiMapper);

		Assert::same(Model\Entity\Client::class, $mapper->getEntityClass('client'));

		$row = LeanMapper\Result::createDetachedInstance()->getRow();
		$row->clientType = 'individual';
		Assert::same(Model\Entity\ClientIndividual::class, $mapper->getEntityClass('client', $row));

		Assert::same('client', $mapper->getTable(Model\Entity\Client::class));
		Assert::same('client', $mapper->getTable(Model\Entity\ClientIndividual::class));
	});
}
