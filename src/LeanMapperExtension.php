<?php

	declare(strict_types=1);

	namespace JP\LeanMapperExtension;

	use CzProject\Assert\Assert;
	use Inlm\Mappers;
	use Nette\DI\Definitions\ServiceDefinition;
	use Nette\DI\ContainerBuilder;
	use Nette\Schema\Expect;
	use Nette\Utils\Strings;
	use Nette;


	class LeanMapperExtension extends \Nette\DI\CompilerExtension
	{
		const NAME_MAPPING_DEFAULT = 'default';
		const NAME_MAPPING_CAMELCASE = 'camelcase';
		const NAME_MAPPING_UNDERSCORE = 'underscore';

		/** @var array<string, class-string> */
		private $nameMappers = [
			self::NAME_MAPPING_DEFAULT => Mappers\DefaultMapper::class,
			self::NAME_MAPPING_CAMELCASE => Mappers\CamelCaseMapper::class,
			self::NAME_MAPPING_UNDERSCORE => Mappers\UnderScoreMapper::class,
		];


		public function getConfigSchema(): Nette\Schema\Schema
		{
			return Expect::structure([
				// services
				'mapper' => Expect::bool()
					->default(TRUE),

				'entityFactory' => Expect::anyOf(
						Expect::string(),
						FALSE
					)
					->default(\LeanMapper\DefaultEntityFactory::class),

				'connection' => Expect::anyOf(
						Expect::string(),
						FALSE
					)
					->default(\LeanMapper\Connection::class),

				// mapper
				'defaultEntityNamespace' => Expect::string()
					->default('Model\\Entity'),

				// connection
				'driver' => Expect::string()
					->default('mysqli'),

				'host' => Expect::string()
					->nullable()
					->default('localhost'),

				'username' => Expect::string()
					->nullable()
					->default(NULL),

				'password' => Expect::string()
					->nullable()
					->default(NULL),

				'database' => Expect::string()
					->nullable()
					->default(NULL),

				'lazy' => Expect::bool()
					->default(TRUE),

				'charset' => Expect::string()
					->default('utf8mb4')
					->nullable(),

				// mapper
				'nameMapping' => Expect::anyOf(
						self::NAME_MAPPING_CAMELCASE,
						self::NAME_MAPPING_DEFAULT,
						self::NAME_MAPPING_UNDERSCORE
					)
					->default(self::NAME_MAPPING_CAMELCASE),

				'entityMapping' => Expect::arrayOf(
						Expect::anyOf(
							Expect::string(),
							Expect::structure([
								'entity' => Expect::string()->required(),
								'repository' => Expect::string()->nullable(),
								'primaryKey' => Expect::string()->nullable(),
							])->castTo('array')
						),
						Expect::string()
					)
					->default([]),

				'prefix' => Expect::string()
					->default(NULL)
					->nullable(),

				'profiler' => Expect::bool()
					->default(NULL),
			])
			->before(function (array $config) {
				// config alias for 'username'
				if (!isset($config['username']) && isset($config['user'])) {
					$config['username'] = $config['user'];
				}

				unset($config['user']);
				return $config;
			})
			->castTo('array');
		}


		public function loadConfiguration()
		{
			$config = $this->getConfig();
			$builder = $this->getContainerBuilder();

			if (!is_array($config)) {
				throw new \RuntimeException("Config must be array, " . gettype($config) . ' given.');
			}

			// use profiler?
			$useProfiler = isset($config['profiler'])
				? $config['profiler']
				: class_exists(\Tracy\Debugger::class) && $builder->parameters['debugMode'];

			unset($config['profiler']);

			// Services
			$connection = $this->configConnection($builder, $config);
			$this->configMapper($builder, $config);
			$this->configEntityFactory($builder, $config);

			// profiler
			if ($connection && $useProfiler) {
				$panel = $builder->addDefinition($this->prefix('panel'))
					->setFactory(\Dibi\Bridges\Tracy\Panel::class);

				$connection->addSetup([$panel, 'register'], [$connection]);
			}
		}


		/**
		 * Adds connection service into container
		 * @param  array<string, mixed> $config
		 */
		protected function configConnection(ContainerBuilder $builder, array $config): ?ServiceDefinition
		{
			$connectionClass = $config['connection'];

			if ($connectionClass === FALSE || $connectionClass === NULL) {
				return NULL;
			}

			if (!is_string($config['connection'])) {
				throw new \RuntimeException('Connection class definition must be string.');
			}

			return $builder->addDefinition($this->prefix('connection'))
				->setFactory($config['connection'], [
					[
						'host' => $config['host'],
						'driver' => $config['driver'],
						'username' => $config['username'],
						'password' => $config['password'],
						'database' => $config['database'],
						'lazy' => (bool) $config['lazy'],
						'charset' => $config['charset'],
					],
				]);
		}


		/**
		 * Adds connection service into container
		 * @param  array<string, mixed> $config
		 */
		protected function configEntityFactory(ContainerBuilder $builder, array $config): ?ServiceDefinition
		{
			$entityFactoryClass = $config['entityFactory'];

			if ($entityFactoryClass === FALSE || $entityFactoryClass === NULL) {
				return NULL;
			}

			if (!is_string($config['entityFactory'])) {
				throw new \RuntimeException('EntityFactory class definition must be string.');
			}

			return $builder->addDefinition($this->prefix('entityFactory'))
				->setFactory($config['entityFactory']);
		}


		/**
		 * Adds mapper service into container
		 * @param  array<string, mixed> $config
		 */
		protected function configMapper(ContainerBuilder $builder, array $config): ?ServiceDefinition
		{
			Assert::bool($config['mapper'], "Option 'mapper' must be bool");

			if (!$config['mapper']) {
				return NULL;
			}

			Assert::stringOrNull($config['defaultEntityNamespace'], "Option 'defaultEntityNamespace' must be string|NULL");
			Assert::string($config['nameMapping'], "Option 'nameMapping' must be string");
			$config['nameMapping'] = Strings::lower($config['nameMapping']);
			Assert::in($config['nameMapping'], array_keys($this->nameMappers), "Invalid value for option 'nameMapping'");

			$nameMapper = $builder->addDefinition($this->prefix('nameMapper'))
				->setFactory($this->nameMappers[$config['nameMapping']], [$config['defaultEntityNamespace']]);
			$mainMapper = $nameMapper;

			$dynamicMapper = $builder->addDefinition($this->prefix('dynamicMapper'));
			$usesDynamicMapper = $this->processEntityProviders($dynamicMapper);
			$usesDynamicMapper = $this->processUserMapping($dynamicMapper, $config) || $usesDynamicMapper;

			if ($usesDynamicMapper) {
				$dynamicMapper->setFactory(Mappers\DynamicMapper::class, [$mainMapper]);
				$mainMapper->setAutowired('self');
				$mainMapper = $dynamicMapper;

			} else {
				$builder->removeDefinition($this->prefix('dynamicMapper'));
			}

			Assert::stringOrNull($config['prefix'], "Option 'prefix' must be string|NULL.");

			if (is_string($config['prefix'])) {
				$prefixMapper = $builder->addDefinition($this->prefix('prefixMapper'))
					->setFactory(Mappers\PrefixMapper::class, [$config['prefix'], $mainMapper]);
				$mainMapper->setAutowired('self');
				$mainMapper = $prefixMapper;
			}

			$mainMapper = $this->configureStiMapper($mainMapper);
			$mainMapper = $this->configureRowMapper($mainMapper);

			return $mainMapper;
		}


		/**
		 * Processes user entities mapping + registers repositories in container
		 * @param  array<string, mixed> $config
		 */
		protected function processUserMapping(ServiceDefinition $mapper, array $config): bool
		{
			$usesMapping = FALSE;

			if (isset($config['entityMapping'])) {
				if (!is_array($config['entityMapping'])) {
					throw new \RuntimeException('Mapping must be array, ' . gettype($config['entityMapping']) . ' given');
				}

				foreach ($config['entityMapping'] as $tableName => $mapping) {
					if (isset($mapping['repository']) && !is_string($mapping['repository'])) {
						throw new \RuntimeException('Repository class must be string or NULL, ' . gettype($mapping['primaryKey']) . ' given');
					}

					if (is_string($mapping)) { // entity class
						$mapping = [
							'entity' => $mapping,
						];
					}

					$mapping['table'] = $tableName;
					$usesMapping = $this->registerInMapper($mapper, $mapping) || $usesMapping;
				}
			}

			return $usesMapping;
		}


		/**
		 * @see    https://github.com/Kdyby/Doctrine/blob/6fc930a79ecadca326722f1c53cab72d56ee2a90/src/Kdyby/Doctrine/DI/OrmExtension.php#L255-L278
		 * @see    http://forum.nette.org/en/18888-extending-extensions-solid-modular-concept
		 */
		protected function processEntityProviders(ServiceDefinition $mapper): bool
		{
			$usesMapping = FALSE;

			foreach ($this->compiler->getExtensions() as $extension) {
				if ($extension instanceof IEntityProvider) {
					$mappings = $extension->getEntityMappings();

					if (is_array($mappings)) {
						foreach ($mappings as $mapping) {
							if (!is_array($mapping)) {
								throw new \InvalidArgumentException('Entity mapping must be array, '. gettype($mapping) . ' given.');
							}
							$usesMapping = $this->registerInMapper($mapper, $mapping) || $usesMapping;
						}

					} elseif (!is_null($mappings)) {
						throw new \InvalidArgumentException('Mappings must be array or NULL, '. gettype($mappings) . ' given.');

					}
				}
			}

			return $usesMapping;
		}


		protected function configureStiMapper(ServiceDefinition $mainMapper): ServiceDefinition
		{
			$builder = $this->getContainerBuilder();
			$stiMapper = $builder->addDefinition($this->prefix('stiMapper'));
			$usesMapping = FALSE;

			foreach ($this->compiler->getExtensions() as $extension) {
				if (!($extension instanceof IStiMappingProvider)) {
					continue;
				}

				$mappings = $extension->getStiMappings();
				Assert::true(is_array($mappings), 'STI mappings from extension must be array.');

				foreach ($mappings as $entityName => $mapping) {
					Assert::true(is_array($mapping), 'STI mapping must be array.');

					if (is_string($entityName)) { // new syntax
						foreach ($mapping as $type => $targetEntity) {
							Assert::string($targetEntity, "STI mapping - target entity name must be string");

							$stiMapper->addSetup('registerStiType', [
								$entityName,
								$type,
								$targetEntity,
							]);
						}

					} else {
						Assert::true(isset($mapping['baseEntity']), "STI mapping - missing key 'baseEntity'");
						Assert::true(isset($mapping['type']), "STI mapping - missing key 'type'");
						Assert::true(isset($mapping['entity']), "STI mapping - missing key 'entity'");

						Assert::string($mapping['baseEntity'], "STI mapping - key 'baseEntity' must be string");
						Assert::true(is_string($mapping['type']) || is_int($mapping['type']), "STI mapping - key 'type' must be string|int");
						Assert::string($mapping['entity'], "STI mapping - key 'entity' must be string");

						$stiMapper->addSetup('registerStiType', [
							$mapping['baseEntity'],
							$mapping['type'],
							$mapping['entity'],
						]);
					}

					$usesMapping = TRUE;
				}

				$typeFields = $extension->getStiTypeFields();
				Assert::true(is_array($typeFields), 'STI type fields from extension must be array.');

				foreach ($typeFields as $baseEntity => $typeField) {
					Assert::string($typeField, "STI type field must be string");

					$stiMapper->addSetup('registerTypeField', [
						$baseEntity,
						$typeField,
					]);
					$usesMapping = TRUE;
				}
			}

			if ($usesMapping) {
				$stiMapper->setFactory(Mappers\StiMapper::class, [$mainMapper]);
				$mainMapper->setAutowired('self');
				$mainMapper = $stiMapper;

			} else {
				$builder->removeDefinition($this->prefix('stiMapper'));
			}

			return $mainMapper;
		}


		protected function configureRowMapper(ServiceDefinition $mainMapper): ServiceDefinition
		{
			$builder = $this->getContainerBuilder();
			$rowMapper = $builder->addDefinition($this->prefix('rowMapper'));
			$usesMapping = FALSE;

			foreach ($this->compiler->getExtensions() as $extension) {
				if (!($extension instanceof IRowMappingProvider)) {
					continue;
				}

				$mappings = $extension->getRowFieldMappings();
				Assert::true(is_array($mappings), 'Row field mapping from extension must be array.');

				foreach ($mappings as $entityName => $mapping) {
					Assert::true(is_array($mapping), 'Row field mapping must be array.');

					if (is_string($entityName)) { // new syntax
						foreach ($mapping as $fieldName => $fieldMapping) {
							if (!is_array($fieldMapping)) {
								throw new \InvalidArgumentException('Row field mapping for field ' . $entityName . '::$' . $fieldName . ' must be array.');
							}

							Assert::string($fieldName, "Row mapping - field name must be string");

							$rowMapper->addSetup('registerFieldMapping', [
								$entityName,
								$fieldName,
								isset($fieldMapping['fromDbValue']) ? $fieldMapping['fromDbValue'] : NULL,
								isset($fieldMapping['toDbValue']) ? $fieldMapping['toDbValue'] : NULL,
							]);
						}

					} else {
						Assert::true(isset($mapping['entity']), "Row mapping - missing key 'entity'");
						Assert::true(isset($mapping['field']), "Row mapping - missing key 'field'");

						Assert::string($mapping['entity'], "Row mapping - key 'entity' must be string");
						Assert::string($mapping['field'], "Row mapping - key 'field' must be string");

						$rowMapper->addSetup('registerFieldMapping', [
							$mapping['entity'],
							$mapping['field'],
							isset($mapping['fromDbValue']) ? $mapping['fromDbValue'] : NULL,
							isset($mapping['toDbValue']) ? $mapping['toDbValue'] : NULL,
						]);
					}

					$usesMapping = TRUE;
				}

				$mappings = $extension->getRowMultiValueMappings();
				Assert::true(is_array($mappings), 'Row field mapping from extension must be array.');

				foreach ($mappings as $entityName => $mapping) {
					Assert::true(is_array($mapping), 'Row field mapping must be array.');

					if (is_string($entityName)) { // new syntax
						foreach ($mapping as $fieldName => $fieldMapping) {
							if (!is_array($fieldMapping)) {
								throw new \InvalidArgumentException('Row field mapping for field ' . $entityName . '::$' . $fieldName . ' must be array.');
							}

							Assert::string($fieldName, "Row mapping - field name must be string");

							$rowMapper->addSetup('registerMultiValueMapping', [
								$entityName,
								$fieldName,
								isset($fieldMapping['fromDbValue']) ? $fieldMapping['fromDbValue'] : NULL,
								isset($fieldMapping['toDbValue']) ? $fieldMapping['toDbValue'] : NULL,
							]);
						}

					} else {
						Assert::true(isset($mapping['entity']), "Row mapping - missing key 'entity'");
						Assert::true(isset($mapping['field']), "Row mapping - missing key 'field'");

						Assert::string($mapping['entity'], "Row mapping - key 'entity' must be string");
						Assert::string($mapping['field'], "Row mapping - key 'field' must be string");

						$rowMapper->addSetup('registerMultiValueMapping', [
							$mapping['entity'],
							$mapping['field'],
							isset($mapping['fromDbValue']) ? $mapping['fromDbValue'] : NULL,
							isset($mapping['toDbValue']) ? $mapping['toDbValue'] : NULL,
						]);
					}

					$usesMapping = TRUE;
				}
			}

			if ($usesMapping) {
				$rowMapper->setFactory(Mappers\RowMapper::class, [$mainMapper]);
				$mainMapper->setAutowired('self');
				$mainMapper = $rowMapper;

			} else {
				$builder->removeDefinition($this->prefix('rowMapper'));
			}

			return $mainMapper;
		}


		/**
		 * Registers new entity in mapper
		 * @param  array $mapping  [table => '', primaryKey => '', entity => '', repository => '']
		 * @phpstan-param array<string, mixed> $mapping
		 */
		protected function registerInMapper(ServiceDefinition $mapper, array $mapping = NULL): bool
		{
			if ($mapping === NULL) {
				return FALSE;
			}

			if (!isset($mapping['table']) || !is_string($mapping['table'])) {
				throw new \InvalidArgumentException('Table name missing or it\'s not string');
			}

			if (!isset($mapping['entity']) || !is_string($mapping['entity'])) {
				throw new \InvalidArgumentException('Entity class missing or it\'s not string');
			}

			$repositoryClass = isset($mapping['repository']) ? $mapping['repository'] : NULL;
			$primaryKey = isset($mapping['primaryKey']) ? $mapping['primaryKey'] : NULL;

			if (!is_string($repositoryClass) && !is_null($repositoryClass)) {
				throw new \InvalidArgumentException('Repository class must be string or NULL, ' . gettype($repositoryClass) . ' given');
			}

			if (!is_string($primaryKey) && !is_null($primaryKey)) {
				throw new \InvalidArgumentException('Primary key must be string or NULL, ' . gettype($primaryKey) . ' given');
			}

			$mapper->addSetup('setMapping', [$mapping['table'], $mapping['entity'], $repositoryClass, $primaryKey]);
			return TRUE;
		}


		/**
		 * @param  string $name
		 * @return void
		 */
		public static function register(\Nette\Configurator $configurator, $name = 'leanmapper')
		{
			if ($configurator->onCompile === NULL) { // @phpstan-ignore-line
				$configurator->onCompile = [];

			} elseif (!is_array($configurator->onCompile)) {
				throw new \RuntimeException("Configurator::onCompile must be array, iterable " . gettype($configurator->onCompile) . ' given.');
			}

			$configurator->onCompile[] = function (\Nette\Configurator $configurator, Nette\DI\Compiler $compiler) use ($name) {
				$compiler->addExtension($name, new LeanMapperExtension());
			};
		}
	}
