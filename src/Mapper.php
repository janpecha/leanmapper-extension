<?php

	namespace JP\LeanMapperExtension;

	use LeanMapper\Exception\InvalidStateException;
	use LeanMapper\Row;


	class Mapper extends \LeanMapper\DefaultMapper
	{
		const K_ENTITY_CLASS = 'entityClass',
			K_PRIMARY_KEY = 'primaryKey';

		/** @var  array  [tableName => [entityClass, primaryKey]] */
		protected $tables;

		/** @var  array  [entityClass => tableName] */
		protected $entities;

		/** @var  array  [repositoryClass => tableName] */
		protected $repositories;


		public function __construct($defaultEntityNamespace = NULL)
		{
			if (is_string($defaultEntityNamespace)) {
				$this->defaultEntityNamespace = $defaultEntityNamespace;
			}
		}


		/**
		 * Registers entity
		 * @param  string|NULL  table name in database
		 * @param  string|NULL
		 * @param  string|NULL
		 * @param  string|NULL
		 * @return self
		 */
		public function register($tableName, $entityClass = NULL, $repositoryClass = NULL, $primaryKey = NULL)
		{
			if (isset($this->tables[$tableName])) {
				throw new InvalidStateException("Table '$tableName' is already registered for entity " . $this->tables[$tableName][self::K_ENTITY_CLASS]);
			}

			if (isset($this->entities[$entityClass])) {
				throw new InvalidStateException("Entity $entityClass is already registered for table '{$this->entities[$entityClass]}'");
			}

			if (isset($this->repositories[$repositoryClass])) {
				throw new InvalidStateException("Repository $repositoryClass is already registered for table '{$this->repositories[$repositoryClass]}'");
			}

			$this->tables[$tableName] = [
				self::K_ENTITY_CLASS => $entityClass,
				self::K_PRIMARY_KEY => $primaryKey,
			];

			if (is_string($entityClass)) {
				$this->entities[$entityClass] = $tableName;
			}

			if (is_string($repositoryClass)) {
				$this->repositories[$repositoryClass] = $tableName;
			} elseif ($repositoryClass !== NULL) {
				throw new \RuntimeException('RepositoryClass must be string or NULL, ' . gettype($repositoryClass) . ' given');
			}
			return $this;
		}

		/**
		 * @inheritdoc
		 */
		public function getPrimaryKey($table)
		{
			if (isset($this->tables[$table][self::K_PRIMARY_KEY])) {
				return $this->tables[$table][self::K_PRIMARY_KEY];
			}
			return parent::getPrimaryKey($table);
		}

		/**
		 * @inheritdoc
		 */
		public function getTable($entityClass)
		{
			if (isset($this->entities[$entityClass])) {
				return $this->entities[$entityClass];
			}
			return parent::getTable($entityClass);
		}

		/**
		 * @inheritdoc
		 */
		public function getEntityClass($table, Row $row = NULL)
		{
			if (isset($this->tables[$table][self::K_ENTITY_CLASS])) {
				return $this->tables[$table][self::K_ENTITY_CLASS];
			}
			return parent::getEntityClass($table, $row);
		}

		/**
		 * @inheritdoc
		 */
		public function getTableByRepositoryClass($repositoryClass)
		{
			if (isset($this->repositories[$repositoryClass])) {
				return $this->repositories[$repositoryClass];
			}
			return parent::getTableByRepositoryClass($repositoryClass);
		}
	}
