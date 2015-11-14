<?php
	/**
	 * @author  Jan Pecha, <janpecha@email.cz>
	 */

	namespace JP\LeanMapperExtension;
	use Nette\DI\ServiceDefinition;
	use Nette\DI\ContainerBuilder;
	use Nette;

	class LeanMapperExtension extends \Nette\DI\CompilerExtension
	{
		public $defaults = array(
			// services
			'mapper' => 'JP\LeanMapperExtension\Mapper',
			'entityFactory' => 'LeanMapper\DefaultEntityFactory',
			'connection' => 'LeanMapper\Connection',

			// mapper
			'defaultEntityNamespace' => NULL,

			// connection
			'host' => 'localhost',
			'driver' => 'mysqli',
			'username' => NULL,
			'password' => NULL,
			'database' => NULL,
			'lazy' => TRUE,

			// entities
			'entities' => NULL,
		);


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
				: class_exists('Tracy\Debugger') && $builder->parameters['debugMode'];

			unset($config['profiler']);

			// Services
			$connection = $this->configConnection($builder, $config);
			$mapper = $this->configMapper($builder, $config);

			$builder->addDefinition($this->prefix('entityFactory'))
				->setClass($config['entityFactory']);

			// profiler
			if ($useProfiler) {
				$panel = $builder->addDefinition($this->prefix('panel'))
					->setClass('Dibi\Bridges\Tracy\Panel');

				$connection->addSetup(array($panel, 'register'), array($connection));
			}
		}


		/**
		 * Adds connection service into container
		 * @return ServiceDefinition
		 */
		protected function configConnection(ContainerBuilder $builder, array $config)
		{
			if (!isset($config['connection']) || !is_string($config['connection'])) {
				throw new \RuntimeException('Connection class definition is missing, or not (string).');
			}

			return $builder->addDefinition($this->prefix('connection'))
				->setClass($config['connection'], array(
					array(
						'host' => $config['host'],
						'driver' => $config['driver'],
						'username' => $config['username'],
						'password' => $config['password'],
						'database' => $config['database'],
						'lazy' => (bool) $config['lazy'],
					),
				));
		}


		/**
		 * Adds mapper service into container
		 * @return ServiceDefinition
		 */
		protected function configMapper(ContainerBuilder $builder, array $config)
		{
			if ($config['defaultEntityNamespace'] !== NULL && !is_string($config['defaultEntityNamespace'])) {
				throw new \RuntimeException('DefaultEntityNamespace must be NULL or string, ' . gettype($config['defaultEntityNamespace']) . ' given');
			}

			$mapper = $builder->addDefinition($this->prefix('mapper'))
				->setClass($config['mapper'], array($config['defaultEntityNamespace']));

			$this->processEntityProviders($mapper, $config);
			$this->processUserEntities($mapper, $config);

			return $mapper;
		}


		/**
		 * Processes user entities definitions + registers repositories in container
		 * @return void
		 */
		protected function processUserEntities(ServiceDefinition $mapper, array $config)
		{
			$builder = $this->getContainerBuilder();

			if (isset($config['entities'])) {
				if (!is_array($config['entities'])) {
					throw new \RuntimeException('List of entities must be array, ' . gettype($config['entities']) . ' given');
				}

				foreach ($config['entities'] as $tableName => $mapping) {
					if (isset($mapping['repository']) && !is_string($mapping['repository'])) {
						throw new \RuntimeException('Repository class must be string or NULL, ' . gettype($mapping['primaryKey']) . ' given');
					}

					if (is_string($mapping)) { // entity class
						$mapping = array(
							'entity' => $mapping,
						);
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
						throw new \InvalidArgumentException('Mappings must be array or NULL, '. gettype($mapping) . ' given.');
					}

					if (is_array($mappings)) {
						foreach ($mappings as $mapping) {
							if (!is_array($mappings) && !is_null($mappings)) {
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

			$mapper->addSetup('register', array($mapping['table'], $mapping['entity'], $repositoryClass, $primaryKey));
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
