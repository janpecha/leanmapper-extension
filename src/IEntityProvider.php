<?php

	declare(strict_types=1);

	namespace JP\LeanMapperExtension;


	interface IEntityProvider
	{
		/**
		 * @return array|NULL  [[table, primaryKey, entity, repository],...]
		 * @phpstan-return array<array<string, mixed>>|NULL
		 */
		function getEntityMappings();
	}
