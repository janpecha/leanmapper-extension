<?php

	namespace JP\LeanMapperExtension;


	interface IRowMappingProvider
	{
		/**
		 * @return array  [[entity, field, fromDbValue, toDbValue],...]
		 */
		function getRowFieldMappings();


		/**
		 * @return array  [[entity, field, fromDbValue, toDbValue],...]
		 */
		function getRowMultiValueMappings();
	}
