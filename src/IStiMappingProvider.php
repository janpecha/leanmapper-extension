<?php

	namespace JP\LeanMapperExtension;


	interface IStiMappingProvider
	{
		/**
		 * @return array  [[baseEntity, type, entity],...]
		 */
		function getStiMappings();


		/**
		 * @return array<string, string>  [baseEntity => typeField]
		 */
		function getStiTypeFields();
	}
