<?php

namespace
{
	require __DIR__ . '/bootstrap.php';
}


namespace Foo\DI
{

	use Nette\DI\CompilerExtension;
	use JP\LeanMapperExtension\IRowMappingProvider;

	class FooExtension extends CompilerExtension implements IRowMappingProvider
	{
		function getRowFieldMappings()
		{
			return [
				[
					'entity' => \Model\Entity\OrderItem::class,
					'field' => 'currency',
					'fromDbValue' => [static::class, 'currencyFromDb'],
					'toDbValue' => [static::class, 'currencyToDb'],
				],
				// ...
			];
		}


		function getRowMultiValueMappings()
		{
			return [
				[
					'entity' => \Model\Entity\OrderItem::class,
					'field' => 'price',
					'fromDbValue' => [static::class, 'priceFromDb'],
					'toDbValue' => [static::class, 'priceToDb'],
				],
			];
		}


		static function currencyFromDb($value)
		{
			return strtoupper($value);
		}


		static function currencyToDb($value)
		{
			return strtolower($value);
		}


		static function priceFromDb(array $values)
		{
			return [$values['price'], $values['currency']];
		}


		static function priceToDb($value)
		{
			return [
				'price' => $value[0],
			];
		}
	}
}

namespace
{
	use Tester\Assert;


	test(function () {
		$container = createContainer('rowMapping');

		$mapper = $container->getByType(LeanMapper\IMapper::class);
		Assert::true($mapper instanceof Inlm\Mappers\RowMapper);

		$dbData = [
			'id' => 1,
			'price' => 123,
			'currency' => 'eur',
		];

		$rowData = [
			'id' => 1,
			'price' => [123, 'EUR'],
			'currency' => 'EUR',
		];

		Assert::same($rowData, $mapper->convertToRowData('orderItem', $dbData));
		Assert::same($dbData, $mapper->convertFromRowData('orderItem', $rowData));
	});
}
