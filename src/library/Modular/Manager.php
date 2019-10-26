<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework\Modular
{
	use RazyFramework\ConfigFile;
	use RazyFramework\ErrorHandler;
	use RazyFramework\RegexHelper;
	use RazyFramework\Scavenger;
	use RazyFramework\Template;

	/**
	 * A Razy Framework module manager. it can control and manage its module package and setup system routing. When the system starts, it will scan available module in the specified folder which is configured in the config file. In additional, You can also access other module distribution's API by creating a new Manager instance in the same host if you have grant access.
	 */
	class Manager
	{
		use \RazyFramework\Injector;

		/**
		 * The wrapper object of the entry distribution.
		 *
		 * @var Wrapper
		 */
		private static $instance;

		/**
		 * An array contains multiple domain setting with its module distribution path.
		 *
		 * @var array
		 */
		private static $multisite = [];

		/**
		 * An array contains multiple domain alias.
		 *
		 * @var array
		 */
		private static $domainalias = [];

		/**
		 * The domain alias.
		 *
		 * @var string
		 */
		private $alias = '';

		/**
		 * The current domain.
		 *
		 * @var string
		 */
		private $domain = '';

		/**
		 * The routed domain name set in sites.php.
		 *
		 * @var string
		 */
		private $domainRoute = '';

		/**
		 * The module distribution code.
		 *
		 * @var string
		 */
		private $distCode = '';

		/**
		 * An array contains repository list.
		 *
		 * @var array
		 */
		private $repo = [];

		/**
		 * The module distribution folder.
		 *
		 * @var string
		 */
		private $distPath = '';

		/**
		 * An array contains module package.
		 *
		 * @var array
		 */
		private $packages = [];

		/**
		 * An array contains the required package.
		 *
		 * @var array
		 */
		private $required = [];

		/**
		 * The relative path of the module distribution.
		 *
		 * @var string
		 */
		private $relativePath = '/';

		/**
		 * The url query used for module routing.
		 *
		 * @var string
		 */
		private $urlQuery = '';

		/**
		 * An array contains the routing prefix with the module package.
		 *
		 * @var array
		 */
		private $routingPrefixMap = [];

		/**
		 * A ConfigFile object contains each package version.
		 *
		 * @var ConfigFile
		 */
		private $packageVersion;

		/**
		 * A ConfigFile object contains each package config.
		 *
		 * @var ConfigFile
		 */
		private $config;

		/**
		 * The locked module package.
		 *
		 * @var Package
		 */
		private $locked;

		/**
		 * The routed module package.
		 *
		 * @var Package
		 */
		private $routed;

		/**
		 * The caller trace list.
		 *
		 * @var array
		 */
		private $trace = [];

		/**
		 * The Template Manager object.
		 *
		 * @var Template\Manager
		 */
		private $tplManager;

		/**
		 * An array contains each package exchange wrapper.
		 *
		 * @var array
		 */
		private $wrappers = [];

		/**
		 * Set true if the Manager is the main instance. The main Manager is allowed to route.
		 *
		 * @var bool
		 */
		private $isMain = false;

		/**
		 * Manager constructor.
		 *
		 * @param string $domain   The domain name to load the module distribution, if the domain is not given, use the current SERVER_HOST
		 * @param string $urlQuery The url query path
		 */
		public function __construct(string $domain = '', string $urlQuery = '')
		{
			$regex = new RegexHelper('/^(?:(?:([a-z0-9][\w-]*(?<![-_]))|\*)\.)*(?:[a-z]{2,}|\*)|((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\.|$)){4}$/');
			if (!$domain) {
				$domain = $_SERVER['HTTP_HOST'];
				if ($pos = false !== strrpos($domain, ':')) {
					$domain = substr($domain, 0, $pos);
				}
			} elseif (!$regex->match($domain)) {
				throw new ErrorHandler('Invalid domain name or IP.');
			}

			$urlQuery     = trim($urlQuery);
			$domain       = trim(ltrim($domain, '.'));
			$this->domain = $domain;

			$this->tplManager = new Template\Manager();

			// Get the path value from the multisite and alias list by th current domain
			if (isset(self::$multisite[$domain])) {
				$this->alias       = $domain;
				$this->domainRoute = $domain;
				$distPath          = self::$multisite[$domain];
			} elseif (isset(self::$domainalias[$domain])) {
				$distPath          = self::$multisite[self::$domainalias[$domain]];
				$this->alias       = $domain;
				$this->domainRoute = self::$domainalias[$domain];
			} else {
				foreach (self::$multisite as $pattern => $path) {
					if ('*' !== $pattern && false !== strpos($pattern, '*')) {
						$wildcard = str_replace('*', '[^.]+', $pattern);
						if (preg_match('/^' . $wildcard . '$/', $domain)) {
							$this->domainRoute = $pattern;

							break;
						}
					}
				}

				if (!$this->domainRoute) {
					if (isset(self::$multisite['*'])) {
						$this->domainRoute = '*';
					} else {
						// No domain matched in multisite or alias config
						ErrorHandler::Show404();
					}
				}
			}

			if ($distPath) {
				// Extract the url query path
				$urlQuery = (RELATIVE_ROOT) ? substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], RELATIVE_ROOT) + \strlen(RELATIVE_ROOT)) : $_SERVER['REQUEST_URI'];
				$urlQuery = tidy(strtok($urlQuery, '?'), true, '/');
				if (\is_array($distPath)) {
					sort_path_level($distPath);
					foreach ($distPath as $routes => $path) {
						$routes = tidy($routes, true, '/');
						if (0 === ($pos = strpos($urlQuery, $routes))) {
							$this->distPath     = tidy($path, true);
							$this->relativePath = $routes;
							$this->urlQuery     = substr($urlQuery, \strlen($routes) - 1);

							break;
						}
					}
				} else {
					$this->distPath = tidy($distPath, true);
					$this->urlQuery = $urlQuery;
				}
			}

			if (!$this->distPath) {
				// No distribution path found or matched by routing
				ErrorHandler::Show404();
			}

			$distPathConfigPath = append($this->distPath, 'dist.php');
			if (is_file($distPathConfigPath)) {
				$distPathConfig = require $distPathConfigPath;
				if (!isset($distPathConfig['dist']) || !$distPathConfig['dist']) {
					throw new ErrorHandler('Missing distribution code or the dist.php is not valided.');
				}

				$distCode = trim($distPathConfig['dist']);
				if (!preg_match('/^[\w]+(\.[\w]+)*$/', $distCode)) {
					throw new ErrorHandler('Distribution code ' . $distCode . ' is not a correct format.');
				}

				$this->distPathCode = $distCode;

				// Setup the cookie and session
				session_set_cookie_params(0, '/', HOSTNAME);
				session_name($this->distPathCode);
				session_start();

				(Scavenger::GetWorker())->register($this->wrapper(['wipe']));

				if (!self::$instance) {
					self::$instance = $this->wrapper(['preload', 'autoload']);
				} else {
					throw new ErrorHandler('You cannot create domain ' . $this->getIdentifyName() . ' module manager again due to there is a instance has been created.');
				}

				// Load the version.php
				$this->packageVersion = new ConfigFile(append(SYSTEM_ROOT, 'config', $this->getIdentifyName(true), 'version.php'));

				// Load the config.php
				$this->config = new ConfigFile(append(SYSTEM_ROOT, 'config', $this->getIdentifyName(true), 'config.php'));

				// Load Module
				$this->loadPackage($this->distPath);

				// Initialize all module package and configuring the routing prefix
				foreach ($this->packages as $packageCode => $package) {
					$this->require($packageCode);
				}

				// Prepare all module package
				foreach ($this->packages as $packageCode => $package) {
					if (Package::STATUS_ACTIVE === $package->getStatus()) {
						$this->wrappers[$package->getCode()]->ready($this->urlQuery);
						$this->routingPrefixMap[$package->getRoutingPrefix()] = $package;
					}
				}

				sort_path_level($this->routingPrefixMap);
				$this->packageVersion->save();
			} else {
				// No distribution config file exists in target path
				ErrorHandler::Show404();
			}
		}

		/**
		 * Load the required module.
		 *
		 * @param string $packageCode    The module code
		 * @param string $versionCompare The version requirement of required module
		 *
		 * @return bool Return true if the module is loaded successfully
		 */
		public function require(string $packageCode, string $versionCompare = '')
		{
			$packageCode = trim($packageCode);
			if (isset($this->packages[$packageCode])) {
				$package = $this->packages[$packageCode];
				if (Package::STATUS_ACTIVE === $package->getStatus()) {
					return true;
				}

				if (Package::STATUS_PENDING === $package->getStatus()) {
					// If the package version is not meet the given version requirement
					if ($versionCompare && !vc($versionCompare, $package->getVersion())) {
						return false;
					}

					$this->required[$packageCode] = true;
					$require                      = $package->getRequire();
					if (\count($require)) {
						foreach ($require as $code => $version) {
							if (isset($this->required[$code]) || $code === $packageCode) {
								continue;
							}

							if (!isset($this->packages[$code])) {
								return false;
							}

							if (Package::STATUS_PENDING === $this->packages[$code]->getStatus()) {
								if (!$this->require($code, $version)) {
									return false;
								}
							}
						}
					}

					$package->initialize();
					if (Package::STATUS_LOADED === $package->getStatus()) {
						if ($this->wrappers[$package->getCode()]->prepare()) {
							if ($package->checkUpdate($this->packageVersion[$packageCode] ?? '')) {
								// Update the version
								$this->packageVersion[$packageCode] = $package->getVersion();
							}

							return true;
						}
					}
				}
			}

			return false;
		}

		/**
		 * Do system route with given query URL.
		 *
		 * @return bool Return true if the route is success
		 */
		public function route()
		{
			foreach ($this->packages as $packageCode => $package) {
				if (Package::STATUS_ACTIVE === $package->getStatus()) {
					$this->wrappers[$packageCode]->standby();
				}
			}

			// If there is a module package is locked.
			if ($this->locked) {
				if (!$this->doRouting($this->locked)) {
					// If the url query is not matched with the locked module package, redirect to the package root
					header('location: ' . $this->locked->getRootURL());
					exit;
				}

				return true;
			}

			foreach ($this->routingPrefixMap as $routingPrefix => $package) {
				if ($this->doRouting($package)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check the specify package is active.
		 *
		 * @param string $code The module code
		 *
		 * @return bool Return true if the package is active
		 */
		public function isPackageActive(string $code)
		{
			if (isset($this->packages[$code])) {
				return Package::STATUS_ACTIVE === $this->packages[$code]->getStatus();
			}

			return false;
		}

		/**
		 * Check the module package is installed.
		 *
		 * @param string $code           The module code
		 * @param string $versionCompare Specify to compare the version
		 *
		 * @return bool Return true if the module is installed and meet the version requirement if it is given
		 */
		public function installed(string $code, string $versionCompare = '')
		{
			if (isset($this->packageVersion[$code])) {
				if ($versionCompare && !vc($this->packageVersion[$code]->getVersion(), $versionCompare)) {
					return false;
				}

				return true;
			}

			return false;
		}

		/**
		 * Force route to sepecify package.
		 *
		 * @param Package $package The package to route to
		 * @param string  $path    The URL Query for routing
		 *
		 * @return bool Return false if Manager cannot route to target package
		 */
		public function routeTo(Package $package, string $path)
		{
			if ($this->locked) {
				if ($this->locked !== $package) {
					// If the Manager has locked a package, it cannot move to other package
					return false;
				}
			}

			header('Location: ' . append($package->getRootURL(), $path));
			exit;

			return true;
		}

		/**
		 * Get the relative path of the routed site.
		 *
		 * @return string The relative path
		 */
		public function getRelativePath()
		{
			return $this->relativePath;
		}

		/**
		 * Get the root url with the protocal.
		 *
		 * @return string The root url
		 */
		public function getRootURL()
		{
			$protocal = (is_ssl()) ? 'https' : 'http';

			return $protocal . '://' . $this->domain . '/';
		}

		/**
		 * Get the site root url.
		 *
		 * @return string the site root url
		 */
		public function getSiteURL()
		{
			return append($this->getRootURL(), RELATIVE_ROOT, $this->relativePath);
		}

		/**
		 * Get the full list of package version in config file.
		 *
		 * @return array An array contains the package version
		 */
		public function getPackageVersion()
		{
			$packageVersion = [];
			foreach ($this->packages as $packageCode => $package) {
				if (Package::STATUS_ACTIVE === $package->getStatus()) {
					$packageVersion[$packageCode] = $package->getVersion();
				}
			}

			return $packageVersion;
		}

		/**
		 * Get all package information, including unloaded and disabled package.
		 *
		 * @return array An array contains package information
		 */
		public function getPackageInfo()
		{
			$packageInfo = [];
			foreach ($this->packages as $packageCode => $package) {
				if (Package::STATUS_ACTIVE === $package->getStatus()) {
					$status = 'active';
				} elseif (Package::STATUS_LOADED === $package->getStatus()) {
					$status = 'loaded';
				} elseif (Package::STATUS_UNLOADED === $package->getStatus()) {
					$status = 'unloaded';
				} elseif (Package::STATUS_PENDING === $package->getStatus()) {
					$status = 'pending';
				} elseif (Package::STATUS_DISABLED === $package->getStatus()) {
					$status = 'disabled';
				}

				$packageInfo[$packageCode] = [
					'module_code' => $packageInfo,
					'version'     => $package->getVersion(),
					'author'      => $package->getAuthor(),
					'status'      => $status,
				];
			}

			return $packageInfo;
		}

		/**
		 * Get the URL Query.
		 *
		 * @return string
		 */
		public function getURLQuery()
		{
			return $this->urlQuery;
		}

		/**
		 * Get the routed module package.
		 *
		 * @return Package The routed module package
		 */
		public function getRoutedPackage()
		{
			return $this->routed;
		}

		/**
		 * Get the distribution code.
		 *
		 * @return string The distribution code
		 */
		public function getDistCode()
		{
			return $this->distPathCode;
		}

		/**
		 * Get the distribution path.
		 *
		 * @return string The distribution path
		 */
		public function getDistPath()
		{
			return $this->distPath;
		}

		/**
		 * Autoload the class from all created module instance.
		 *
		 * @param string $class The class name
		 *
		 * @return bool Return true if the class is declared successfully
		 */
		public static function SPLAutoload(string $class)
		{
			if (self::$instance) {
				return self::$instance->autoload($class);
			}

			return false;
		}

		/**
		 * Setup multiple domain configuration.
		 */
		public static function Initialize()
		{
			if (!\defined('SYSTEM_ROOT')) {
				throw new ErrorHandler('SYSTEM_ROOT is not defined, initial failed.');
			}

			$regex = new RegexHelper('/^(?:(?:([a-z0-9][\w-]*(?<![-_]))|\*)\.)*(?:[a-z]{2,}|\*)|((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\.|$)){4}$/');

			$sitesConfig = require SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'sites.inc.php';
			if (\is_array($sitesConfig['domains'])) {
				foreach ($sitesConfig['domains'] as $domain => $path) {
					$domain = trim(ltrim($domain, '.'));
					if ($regex->test($domain)) {
						if (\is_string($path) || \is_array($path)) {
							self::$multisite[$domain] = $path;
						}
					}
				}
			}

			if (\is_array($sitesConfig['alias'])) {
				foreach ($sitesConfig['alias'] as $alias => $domain) {
					$domain = trim(ltrim($domain, '.'));
					$alias  = trim(ltrim($alias, '.'));
					if ($regex->test($domain) && $regex->test($alias)) {
						if (isset(self::$multisite[$domain])) {
							self::$domainalias[$alias] = $domain;
						}
					}
				}
			}
		}

		/**
		 * Call the data to package registered method via Modular API.
		 *
		 * @param string   $command The API command
		 * @param mixed... $args    The argument pass to API method
		 *
		 * @return mixed The result returned from API
		 */
		public function api(string $command, ...$args)
		{
			$stack   = debug_backtrace(0, 1);
			$args    = $stack[0]['args'];
			$command = array_shift($args);

			if (!preg_match('/^\w+\.\w+$/i', $command)) {
				throw new ErrorHandler('The command name ' . $command . ' is not in a correct format.');
			}
			list($name, $mapping) = explode('.', $command);

			if ($name && $mapping) {
				if (isset($this->packages[$name]) && Package::STATUS_ACTIVE === $this->packages[$name]->getStatus()) {
					// Get the current trace list
					$trace = $this->trace;

					// Add the current package to the trace list
					$this->trace[] = $name;

					// Execute the API method
					$result = $this->wrappers[$name]->execute($mapping, $args, $trace);

					// Pop the last package from the trace list
					array_pop($this->trace);

					return $result;
				}
			}

			return false;
		}

		/**
		 * Trigger the event`.
		 *
		 * @param string   $event The event name
		 * @param mixed... $args  The argument pass to event method
		 *
		 * @return \stdClass The object contains the args and each package response data
		 */
		public function event(string $event, ...$args)
		{
			if (!preg_match('/^\w+$/i', $event)) {
				throw new ErrorHandler('The event name ' . $event . ' is not in a correct format.');
			}

			foreach ($args as $k => &$v) {
				// Pass-by-reference
			}

			$object = (object) [
				'event'    => $event,
				'args'     => $args,
				'response' => [],
			];

			foreach ($this->packages as $code => $package) {
				if (Package::STATUS_ACTIVE === $package->getStatus()) {
					// Get the current trace list
					$trace = $this->trace;

					// Add the current package to the trace list
					$this->trace[] = $code;

					if ($package->hasEvent($event)) {
						// Execute the event method
						$object->response[$package->getCode()] = $this->wrappers[$package->getCode()]->trigger($event, $object->args, $trace);
					}
					// Pop the last package from the trace list
					array_pop($this->trace);
				}
			}

			return $object;
		}

		/**
		 * Copy the file from source to target directory.
		 *
		 * @param Package $package    The package to locate the data storage
		 * @param string  $sourcePath The file source
		 * @param string  $directory  The directory in distribution folder
		 * @param string  $filename   The filename in target directory, leave blank as the source file name
		 *
		 * @return string The target path
		 */
		private function moveFile(Package $package, string $sourcePath, string $directory = '/', string $filename = '')
		{
			if ($this === $package->getManager()) {
				if (is_file($sourcePath)) {
					if (!$filename) {
						$filename = basename($sourcePath);
					}
					$directory = append(SYSTEM_ROOT, 'data', $this->getIdentifyName(true), $directory);
					if (!is_dir($directory)) {
						if (is_file($directory)) {
							throw new ErrorHandler($directory . ' is not a directory.');
						}
						// Create the directory
						mkdir($directory, 0755, true);
					}

					$target = append($directory, $filename);
					if (!copy($sourcePath, $target)) {
						return '';
					}

					return $target;
				}
			}

			return '';
		}

		/**
		 * Get the storage file path
		 *
		 * @param Package $package    The package to locate the data storage
		 * @param string  $path   		The file path in target directory
		 * @param string  $distCode   The distribution code under the current domain
		 *
		 * @return string Return the file path URL or return empty string if the file is not exists
		 */
		private function getStorageFilePath(Package $package, string $path, string $distCode = '')
		{
			if ($this === $package->getManager()) {
				$distCode = $this->getIdentifyName(true, $distCode);
				$filePath = append(SYSTEM_ROOT, 'data', $distCode, $path);
				if (!is_file($filePath)) {
					return '';
				}

				return append($this->getRootURL(), RELATIVE_ROOT, 'data', $distCode, $path);
			}

			return '';
		}

		/**
		 * Autoload a class from the available module library folder.
		 *
		 * @param string $class The class name waiting for load
		 *
		 * @return bool Return true if the class is required successfully
		 */
		private function autoload(string $class)
		{
			foreach ($this->packages as $code => $package) {
				if (Package::STATUS_INITIALIZING === $package->getStatus() || Package::STATUS_ACTIVE === $package->getStatus()) {
					if ($package->loadLibrary($class)) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Try to routing into package if the url query is matched with its routing prefix.
		 *
		 * @param Package $package The package routing in
		 *
		 * @return bool return true if routed in
		 */
		private function doRouting(Package $package)
		{
			if (Package::STATUS_ACTIVE === $package->getStatus()) {
				if (0 === ($pos = strpos($this->urlQuery, $package->getRoutingPrefix()))) {
					$urlQuery = substr($this->urlQuery, \strlen($package->getRoutingPrefix()) - 1);
					$code     = $package->getCode();

					// Rewrite the url query before routing
					$urlQuery = $this->wrappers[$code]->rewrite($urlQuery);
					if (!\is_string($urlQuery)) {
						$urlQuery = '';
					}

					if ($this->wrappers[$code]->touch($urlQuery)) {
						$this->trace[] = $code;
						$this->routed  = $package;
						if ($this->wrappers[$code]->route($urlQuery)) {
							return true;
						}
					}

					return false;
				}
			}

			return false;
		}

		/**
		 * Lock the module package that only allowed route to, if the url query is not matched with the module package routing, manager will redirect to its root path or the first route.
		 * Beware that you can only lock the package once time, if a package is locked, other packages cannot be locked afterward.
		 *
		 * @param Package $package The package object to lock
		 *
		 * @return self Chainable
		 */
		private function lock(Package $package)
		{
			if ($this === $package->getManager()) {
				if (!$this->locked) {
					$this->locked = $package;
				}
			}

			return $this;
		}

		/**
		 * Update the package version from given package.
		 *
		 * @param Package $package The Package to update the version
		 *
		 * @return self Chainable
		 */
		private function updateVersion(Package $package)
		{
			if ($this === $package->getManager()) {
				$this->packageVersion[$package->getCode()] = $package->getVersion();
				$this->packageVersion->save();
			}

			return $this;
		}

		/**
		 * Save the package's config into config file.
		 *
		 * @param Package $package The Package to save the config
		 * @param array   $config  An array contains the package configuration
		 *
		 * @return self Chainable
		 */
		private function saveConfig(Package $package, array $config)
		{
			if ($this === $package->getManager()) {
				$this->config[$package->getCode()] = $config;
				$this->config->save();
			}

			return $this;
		}

		/**
		 * Get the package's config into config file by the given Package.
		 *
		 * @param Package $package The package to get the config
		 *
		 * @return array An array contains the package configuration
		 */
		private function getConfig(Package $package)
		{
			if ($this === $package->getManager()) {
				return $this->config[$package->getCode()] ?? null;
			}

			return null;
		}

		/**
		 * Load module package in distribution folder.
		 *
		 * @param string $path The package folder
		 *
		 * @return self Chainable
		 */
		private function loadPackage(string $path)
		{
			$path = tidy($path, true);
			if (!is_dir($path)) {
				return $this;
			}

			foreach (scandir($path) as $node) {
				if ('.' === $node || '..' === $node) {
					continue;
				}

				// Get the module path
				$folder = $path . $node . \DIRECTORY_SEPARATOR;

				if (is_dir($folder)) {
					if (is_file($folder . 'dist.php')) {
						// Do not scan the folder if it is a distribution folder
						continue;
					}

					if (is_file($folder . 'package.php')) {
						// Load the module if it has the package.php file
						try {
							$wrapper = $this->wrapper(['lock', 'updateVersion', 'saveConfig', 'getConfig', 'moveFile', 'getStorageFilePath', 'getTplManager', 'routeTo', 'notify']);

							$package = new Package($this, $folder, require $folder . 'package.php', $wrapper);

							$this->wrappers[$package->getCode()] = $wrapper($package->getCode());
							if (!isset($this->packages[$package->getCode()])) {
								$this->packages[$package->getCode()] = $package;
							} else {
								throw new ErrorHandler('Duplicated module package loaded, module load abort.');
							}
						} catch (\Exception $e) {
							throw new ErrorHandler('Unable to load the module package.');
						}
					} else {
						$this->loadPackage($folder);
					}
				}
			}

			return $this;
		}

		/**
		 * Get the domain template manager.
		 *
		 * @return Template\Manager The Template Manager object
		 */
		private function getTplManager()
		{
			return $this->tplManager;
		}

		/**
		 * Get the identify name by its domain and distribution code.
		 *
		 * @param bool 		$safe 			Set true to ouput the file name safe string
		 * @param string  $distCode   The distribution code under the current domain
		 *
		 * @return string The identify name
		 */
		private function getIdentifyName(bool $safe = false, string $distCode = '')
		{
			$domain = $this->domainRoute;

			if ($safe) {
				$domain = str_replace('*', '_', $domain);
			}

			return $domain . '-' . (($distCode) ? $distCode : $this->distPathCode);
		}

		/**
		 * Broadcast to all module to notify the target module is routed.
		 *
		 * @param Package $routedPackage The routed module
		 *
		 * @return self Chainable
		 */
		private function notify(Package $routedPackage)
		{
			foreach ($this->packages as $package) {
				if (Package::STATUS_ACTIVE === $package->getStatus() && $package->getCode() !== $routedPackage->getCode()) {
					$this->wrappers[$package->getCode()]->notify($routedPackage->getCode());
				}
			}

			return $this;
		}

		/**
		 * It will be executed before the script execution is ended.
		 */
		private function wipe()
		{
			return $this;
		}
	}
}
