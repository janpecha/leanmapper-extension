<?php

	namespace JP\LeanMapperExtension;


	interface IRowMappingProvider
	{
		/**
		 * @return array  [[entity, field, fromDbValue, toDbValue],...]
		 * @phpstan-return array<array<string, mixed>>
		 */
		function getRowFieldMappings();


		/**
		 * @return array  [[entity, field, fromDbValue, toDbValue],...]
		 * @phpstan-return array<array<string, mixed>>
		 */
		function getRowMultiValueMappings();
	}
