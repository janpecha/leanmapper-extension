<?php

	namespace JP\LeanMapperExtension;


	interface IStiMappingProvider
	{
		/**
		 * @return array  [[baseEntity, type, entity],...]
		 * @phpstan-return array<array<string, mixed>>
		 */
		function getStiMappings();


		/**
		 * @return array<string, string>  [baseEntity => typeField]
		 */
		function getStiTypeFields();
	}
