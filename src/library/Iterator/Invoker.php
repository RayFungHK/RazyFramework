<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework\Iterator
{
	use RazyFramework\ErrorHandler;
	use RazyFramework\Plugin;

	/**
	 * Iterator Invoker is a container-like object and it contains the key and the value from Iterator Manager. This class method will called by magic method "call" and extendable from closure plugin.
	 */
	class Invoker
	{
		/**
		 * The object contains the key and value.
		 *
		 * @var \stdClass
		 */
		private $keyval;

		/**
		 * The plugin object.
		 *
		 * @var Plugin
		 */
		private $plugin;

		/**
		 * DataInvoker constructor.
		 *
		 * @param object $keyval An object contains the key and value
		 * @param Plugin $plugin The plugin object
		 */
		public function __construct(object $keyval, Plugin $plugin)
		{
			$this->keyval = $keyval;
			$this->plugin = $plugin;
		}

		/**
		 * Magic method __call, load the closure from plugin file and pass the keyval object
		 * to the closure.
		 *
		 * @param string $funcName The called method name
		 * @param array  $args     An array contains arguments
		 *
		 * @return mixed The value returned by closure
		 */
		public function __call(string $funcName, array $args)
		{
			$funcName = trim($funcName);
			if (!$closure = $this->plugin->plugin('conv.' . $funcName)) {
				throw new ErrorHandler('Cannot load [' . $funcName . '] convertor function.');
			}

			// Bind convertor object to closure function
			return call_user_func_array($closure->bindTo($this, __CLASS__), $args);
		}

		/**
		 * Get the reference key-value pair object.
		 *
		 * @return mixed The key-value pair
		 */
		public function getKeyValue()
		{
			return $this->keyval;
		}

		/**
		 * Get the invoker value.
		 *
		 * @return mixed The invoker value
		 */
		public function getValue()
		{
			return $this->keyval->value;
		}

		/**
		 * Get the invoker key.
		 *
		 * @return string the invoker key
		 */
		public function getKey()
		{
			return $this->keyval->key;
		}

		/**
		 * Register an invoker.
		 *
		 * @param string   $name     The method name will be called in magic method __call
		 * @param callable $callback The invoker closure
		 */
		public static function RegisterInvoker(string $name, callable $callback)
		{
			$name = trim($name);
			if (preg_match('/^[\w-]+$/', $name)) {
				if (!isset(self::$filters[$name])) {
					self::$dynamicInvokers[$name] = null;
				}

				if (is_callable($callback)) {
					self::$dynamicInvokers[$name] = $callback;
				}
			}
		}
	}
}
