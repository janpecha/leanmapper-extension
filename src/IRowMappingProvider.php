<?php

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
