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
	use RazyFramework\ErrorHandler;
	use RazyFramework\Template;

	/**
	 * A shortcut loader for module controller.
	 */
	class Loader
	{
		/**
		 * An array contains the loader.
		 *
		 * @var array
		 */
		private static $loaders = [];

		/**
		 * An array contains the module package that liniked to loader.
		 *
		 * @var array
		 */
		private static $packages = [];

		/**
		 * The module package.
		 *
		 * @var Package
		 */
		private $package;

		/**
		 * An array contains the loader closure that bound the package.
		 *
		 * @var array
		 */
		private $closures = [];

		/**
		 * Loader constructor.
		 *
		 * @param Package $package The module package
		 */
		public function __construct(Package $package)
		{
			if (isset(self::$packages[$package->getCode()])) {
				throw new ErrorHandler('You cannot create one more ' . $package->getCode() . ' module package\'s loader.');
			}

			self::$packages[$package->getCode()] = $this;
			$this->package                       = $package;
		}

		/**
		 * A magic method to call the loader method.
		 *
		 * @param string $method    The method name
		 * @param array  $arguments The arguments
		 *
		 * @return mixed The result returned by loader
		 */
		public function __call(string $method, array $arguments)
		{
			if (!isset(self::$loaders[$method])) {
				trigger_error('Call to undefined method ' . __CLASS__ . '::' . $method . '()', E_USER_ERROR);
			}

			if (!isset($this->closures[$method])) {
				$this->closures[$method] = \Closure::bind(self::$loaders[$method], $this->package);
			}

			return \call_user_func_array($this->closures[$method], $arguments);
		}

		/**
		 * Register a new loader.
		 *
		 * @param string   $name     The method name of the loader
		 * @param callable $callback The closure of the loader
		 */
		public static function RegisterLoader(string $name, callable $callback)
		{
			$name = trim($name);
			if (!preg_match('/^(?![0-9]+)[\w]+$/', $name)) {
				throw new ErrorHandler('Invalid method name, it allows a-z, A-Z and _ (underscore) only, also the name cannot start from digit.');
			}

			self::$loaders[$name] = $callback;
		}

		/**
		 * Default loader "load", require the file in module include folder.
		 *
		 * @param string $path    The relative path of the file to require
		 * @param mixed  &$result The result returned by loading the file. e.g. return an array if it is a JSON file, else the plain text.
		 *
		 * @return bool Return true if the file is required successfully
		 */
		public function load(string $path, &$result = null)
		{
			$filepath  = append($this->package->getRootPath(), 'include', $path);
			$extension = pathinfo($filepath, \PATHINFO_EXTENSION);

			if (is_file($filepath)) {
				if ('php' === $extension) {
					require $filepath;
				} elseif ('json' === $extension) {
					$result = json_decode(file_get_contents($filepath), true);

					return null !== $result;
				} else {
					$result = file_get_contents($filepath);
				}

				return true;
			}

			return false;
		}

		/**
		 * Default loader "view", it load thetemplate file in the module view folder.
		 *
		 * @param string $filename The filename to load
		 *
		 * @return Template\Manager The template manager
		 */
		public function view(string $filename, string $name = '')
		{
			// If there is no extension provided, default as .tpl
			if (!preg_match('/\.[a-z]+$/i', $filename)) {
				$filename .= '.tpl';
			}

			$path = append($this->package->getViewPath(), $filename);
			if (!is_file($path)) {
				throw new ErrorHandler('The template file ' . $path . ' not found.');
			}

			$source = $this->package->getTplManager()->load(append($this->package->getViewPath(), $filename));
			$source->assign([
				'site_root'        => $this->package->getManager()->getSiteURL(),
				'module_root'      => $this->package->getRootURL(),
				'module_view'      => $this->package->getViewURL(),
				'module_view_dict' => $this->package->getManager()->getPackageViewDirectory(),
			]);

			return $source;
		}
	}
}
