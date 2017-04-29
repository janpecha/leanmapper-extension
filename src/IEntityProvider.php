<?php

	namespace JP\LeanMapperExtension;


	interface IEntityProvider
	{
		/**
		 * @return array|NULL  [[table, primaryKey, entity, repository],...]
		 */
		function getEntityMappings();
	}
