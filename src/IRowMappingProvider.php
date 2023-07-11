<?php

	declare(strict_types=1);

	namespace JP\LeanMapperExtension;


	interface IRowMappingProvider
	{
		/**
		 * @return array<array<string, mixed>>
		 */
		function getRowFieldMappings();


		/**
		 * @return array<array<string, mixed>>
		 */
		function getRowMultiValueMappings();
	}
