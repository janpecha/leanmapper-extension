<?php

	namespace JP\LeanMapperExtension;

	use CzProject\Assert\Assert;
	use Inlm\Mappers;
	use Nette\DI\ServiceDefinition;
	use Nette\DI\ContainerBuilder;
	use Nette\Utils\Strings;
	use Nette;


	class LeanMapperExtension extends \Nette\DI\CompilerExtension
	{
		const NAME_MAPPING_DEFAULT = 'default';
		const NAME_MAPPING_CAMELCASE = 'camelcase';
		const NAME_MAPPING_UNDERSCORE = 'underscore';

		public $defaults = [
			// services
			'mapper' => TRUE,
			'entityFactory' => \LeanMapper\DefaultEntityFactory::class,
			'connection' => \LeanMapper\Connection::class,

			// mapper
			'defaultEntityNamespace' => NULL,

			// connection
			'host' => 'localhost',
			'driver' => 'mysqli',
			'username' => NULL,
			'password' => NULL,
			'database' => NULL,
			'lazy' => TRUE,
			'charset' => NULL,

			// mapper
			'nameMapping' => self::NAME_MAPPING_CAMELCASE,
			'entityMapping' => NULL,
		];

		private $nameMappers = [
			self::NAME_MAPPING_DEFAULT => Mappers\DefaultMapper::class,
			self::NAME_MAPPING_CAMELCASE => Mappers\CamelCaseMapper::class,
			self::NAME_MAPPING_UNDERSCORE => Mappers\UnderScoreMapper::class,
		];


		public function loadConfiguration()
		{
			$config = $this->getConfig($this->defaults);
			$builder = $this->getContainerBuilder();

			// config alias for 'username'
			if (!isset($config['username']) && isset($config['user'])) {
				$config['username'] = $config['user'];
			}
			unset($config['user']);

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
					->setClass(\Dibi\Bridges\Tracy\Panel::class);

				$connection->addSetup([$panel, 'register'], [$connection]);
			}
		}


		/**
		 * Adds connection service into container
		 * @return ServiceDefinition
		 */
		protected function configConnection(ContainerBuilder $builder, array $config)
		{
			$connectionClass = $config['connection'];

			if ($connectionClass === FALSE || $connectionClass === NULL) {
				return NULL;
			}

			if (!is_string($config['connection'])) {
				throw new \RuntimeException('Connection class definition must be string.');
			}

			return $builder->addDefinition($this->prefix('connection'))
				->setClass($config['connection'], [
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
		 * @return ServiceDefinition
		 */
		protected function configEntityFactory(ContainerBuilder $builder, array $config)
		{
			$entityFactoryClass = $config['entityFactory'];

			if ($entityFactoryClass === FALSE || $entityFactoryClass === NULL) {
				return NULL;
			}

			if (!is_string($config['entityFactory'])) {
				throw new \RuntimeException('EntityFactory class definition must be string.');
			}

			return $builder->addDefinition($this->prefix('entityFactory'))
				->setClass($config['entityFactory']);
		}


		/**
		 * Adds mapper service into container
		 * @return ServiceDefinition|NULL
		 */
		protected function configMapper(ContainerBuilder $builder, array $config)
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
				->setClass($this->nameMappers[$config['nameMapping']], [$config['defaultEntityNamespace']]);
			$mainMapper = $nameMapper;

			$dynamicMapper = $builder->addDefinition($this->prefix('dynamicMapper'));
			$usesDynamicMapper = $this->processEntityProviders($dynamicMapper, $config);
			$usesDynamicMapper |= $this->processUserMapping($dynamicMapper, $config);

			if ($usesDynamicMapper) {
				$dynamicMapper->setClass(Mappers\DynamicMapper::class, [$mainMapper]);
				$mainMapper->setAutowired('self');
				$mainMapper = $dynamicMapper;

			} else {
				$builder->removeDefinition($this->prefix('dynamicMapper'));
			}

			return $mainMapper;
		}


		/**
		 * Processes user entities mapping + registers repositories in container
		 * @return bool
		 */
		protected function processUserMapping(ServiceDefinition $mapper, array $config)
		{
			$builder = $this->getContainerBuilder();
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
					$usesMapping |= $this->registerInMapper($mapper, $mapping);

					// auto-register of repository in Container
					if (isset($mapping['repository'])) {
						$this->registerRepositoryInContainer($builder, $mapping['repository']);
					}
				}
			}

			return $usesMapping;
		}


		/**
		 * @see    https://github.com/Kdyby/Doctrine/blob/6fc930a79ecadca326722f1c53cab72d56ee2a90/src/Kdyby/Doctrine/DI/OrmExtension.php#L255-L278
		 * @see    http://forum.nette.org/en/18888-extending-extensions-solid-modular-concept
		 * @return bool
		 */
		protected function processEntityProviders(ServiceDefinition $mapper, array $config)
		{
			$builder = $this->getContainerBuilder();
			$usesMapping = FALSE;

			foreach ($this->compiler->getExtensions() as $extension) {
				if ($extension instanceof IEntityProvider) {
					$mappings = $extension->getEntityMappings();

					if (!is_array($mappings) && !is_null($mappings)) {
						throw new \InvalidArgumentException('Mappings must be array or NULL, '. gettype($mappings) . ' given.');
					}

					if (is_array($mappings)) {
						foreach ($mappings as $mapping) {
							if (!is_array($mapping) && !is_null($mapping)) {
								throw new \InvalidArgumentException('Entity mapping must be array or NULL, '. gettype($mapping) . ' given.');
							}
							$usesMapping |= $this->registerInMapper($mapper, $mapping);

							if (isset($mapping['repository']) && (!isset($mapping['registerRepository']) || $mapping['registerRepository'])) {
								$this->registerRepositoryInContainer($builder, $mapping['repository']);
							}
						}
					}
				}
			}

			return $usesMapping;
		}


		protected function registerRepositoryInContainer($builder, $repositoryClass)
		{
			if (!is_string($repositoryClass)) {
				throw new \RuntimeException('RepositoryClass must be string, ');
			}
			$repository = strtr($repositoryClass, '\\', '_');
			$builder->addDefinition("repositories.$repository")
				->setClass($repositoryClass);
		}


		/**
		 * Registers new entity in mapper
		 * @param  ServiceDefinition
		 * @param  array  [table => '', primaryKey => '', entity => '', repository => '']
		 * @return bool
		 */
		protected function registerInMapper(ServiceDefinition $mapper, array $mapping = NULL)
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
		 * @param  Nette\Configurator
		 * @param  string
		 * @return void
		 */
		public static function register(Nette\Configurator $configurator, $name = 'leanmapper')
		{
			$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) use ($name) {
				$compiler->addExtension($name, new LeanMapperExtension());
			};
		}
	}
