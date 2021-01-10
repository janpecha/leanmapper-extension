<?php

	namespace JP\LeanMapperExtension;

	use Nette\DI\ServiceDefinition;
	use Nette\DI\ContainerBuilder;
	use Nette;


	class LeanMapperExtension extends \Nette\DI\CompilerExtension
	{
		public $defaults = [
			// services
			'mapper' => Mapper::class, // string|FALSE|NULL
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

			// entities
			'mapping' => NULL,
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
			$mapperClass = $config['mapper'];

			if ($mapperClass === FALSE || $mapperClass === NULL) {
				return NULL;
			}

			if ($config['defaultEntityNamespace'] !== NULL && !is_string($config['defaultEntityNamespace'])) {
				throw new \RuntimeException('DefaultEntityNamespace must be NULL or string, ' . gettype($config['defaultEntityNamespace']) . ' given');
			}

			$mapper = $builder->addDefinition($this->prefix('mapper'))
				->setClass($config['mapper'], [$config['defaultEntityNamespace']]);

			$this->processEntityProviders($mapper, $config);
			$this->processUserMapping($mapper, $config);

			return $mapper;
		}


		/**
		 * Processes user entities mapping + registers repositories in container
		 * @return void
		 */
		protected function processUserMapping(ServiceDefinition $mapper, array $config)
		{
			$builder = $this->getContainerBuilder();

			if (isset($config['mapping'])) {
				if (!is_array($config['mapping'])) {
					throw new \RuntimeException('Mapping must be array, ' . gettype($config['mapping']) . ' given');
				}

				foreach ($config['mapping'] as $tableName => $mapping) {
					if (isset($mapping['repository']) && !is_string($mapping['repository'])) {
						throw new \RuntimeException('Repository class must be string or NULL, ' . gettype($mapping['primaryKey']) . ' given');
					}

					if (is_string($mapping)) { // entity class
						$mapping = [
							'entity' => $mapping,
						];
					}

					$mapping['table'] = $tableName;
					$this->registerInMapper($mapper, $mapping);

					// auto-register of repository in Container
					if (isset($mapping['repository'])) {
						$this->registerRepositoryInContainer($builder, $mapping['repository']);
					}
				}
			}
		}


		/**
		 * @see    https://github.com/Kdyby/Doctrine/blob/6fc930a79ecadca326722f1c53cab72d56ee2a90/src/Kdyby/Doctrine/DI/OrmExtension.php#L255-L278
		 * @see    http://forum.nette.org/en/18888-extending-extensions-solid-modular-concept
		 */
		protected function processEntityProviders(ServiceDefinition $mapper, array $config)
		{
			$builder = $this->getContainerBuilder();

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
							$this->registerInMapper($mapper, $mapping);

							if (isset($mapping['repository']) && (!isset($mapping['registerRepository']) || $mapping['registerRepository'])) {
								$this->registerRepositoryInContainer($builder, $mapping['repository']);
							}
						}
					}
				}
			}
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
		 * @return void
		 */
		protected function registerInMapper(ServiceDefinition $mapper, array $mapping = NULL)
		{
			if ($mapping === NULL) {
				return;
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

			$mapper->addSetup('register', [$mapping['table'], $mapping['entity'], $repositoryClass, $primaryKey]);
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
