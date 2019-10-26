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
	 * An array-like object, it is compatible with the PHP SPL array function. You can control the value with magic method "invoke" by given key or using magic method "call" to control the Iterator Manager object. Also you can use the addRule method to control data consistency.
	 */
	class Manager extends \ArrayObject
	{
		/**
		 * A option to determine show the warning if the key is not exists.
		 *
		 * @var bool
		 */
		private static $displayWarning = false;

		/**
		 * An array contains the rule closure.
		 *
		 * @var array
		 */
		private static $rules = [];

		/**
		 * An array contains the Iterator Manager function.
		 *
		 * @var array
		 */
		private static $functions = [];

		/**
		 * The iterator reflector, it allows the Iterator Manager works in any PHP array_ SPL function.
		 *
		 * @var \Iterator
		 */
		private $iterator;

		/**
		 * The Plugin object.
		 *
		 * @var Plugin
		 */
		private $plugin;

		/**
		 * DataFactory constructor.
		 *
		 * @param array $data An array contains value
		 */
		public function __construct(array $data = [])
		{
			if (!\is_array($data) && !\array_key_exists('ArrayAccess', class_implements($data))) {
				$data = [$data];
			}

			// Get the iterator object used to return the value by reference via offetGet
			$this->iterator = $this->getIterator();

			$this->plugin = new Plugin($this);
			$this->plugin->addPluginFolder(append(PLUGIN_FOLDER, 'Iterator'));

			parent::__construct($data);
		}

		/**
		 * Magic method __call, load the closure from plugin file and pass the DataFactory object to the closure.
		 *
		 * @param string $funcName The called method name
		 * @param array  $args     An array contains arguments
		 *
		 * @return mixed The value returned by closure
		 */
		public function __call(string $funcName, array $args)
		{
			$funcName = trim($funcName);
			if (!$closure = $this->plugin->plugin('func.' . $funcName)) {
				throw new ErrorHandler('Cannot load [' . $funcName . '] convertor function.');
			}

			// Bind convertor object to closure function
			return \call_user_func_array($closure->bindTo($this, __CLASS__), $args);
		}

		/**
		 * Magic method __invoke, obtain the value by given index and return the DataInvoker object.
		 *
		 * @param string $index The index used to get the value in DataFactory
		 *
		 * @return Invoker The {@see Invoker} object
		 */
		public function __invoke(string $index)
		{
			// Create a associate if the key is not exists
			if (!self::offsetExists($index)) {
				$this[$index] = null;
			}

			// Create an object to DataInvoker, and objects are passed by references by default
			// so that you can modify the DataFactory value via DataInvoker
			$object = [
				'key'   => $index,
				'value' => &$this[$index],
			];

			if (!$index) {
				throw new ErrorHandler('The invoke index cannot be empty.');
			}

			return new Invoker((object) $object, $this->plugin);
		}

		/**
		 * Set the option to display the warning if the key is not exists.
		 *
		 * @param bool $enable Enable to display the warning
		 */
		public static function DisplayWarning(bool $enable)
		{
			self::$displayWarning = $enable;
		}

		/**
		 * Override offsetGet method from \ArrayObject, return the value from iterator reflector by given key.
		 *
		 * @param int|string $index The key of the array
		 */
		public function &offsetGet($index)
		{
			// Prevent display undefine warning
			if (self::$displayWarning && !$this->offsetExists($index)) {
				trigger_error('Undefined offset: ' . $index, E_USER_WARNING);

				return null;
			}

			$value = &$this->iterator[$index] ?? null;

			return $value;
		}

		/**
		 * Overrides offsetSet method from \ArrayObject, pass the value to the rule closure before set the value.
		 *
		 * @param int|string $index The key of the iterator
		 * @param mixed      $value The value to set to the iterator
		 */
		public function offsetSet($index, $value)
		{
			parent::offsetSet($index, $value);
		}

		/**
		 * Adds a validation rule for the given key.
		 *
		 * @param string   $index    The key of the iterator
		 * @param callable $callback The rule closure
		 *
		 * @return self Chainable
		 */
		public function addRule(string $index, callable $callback)
		{
			$this->rules[$index] = $callback;
			// If the key is exists already, run the validation rule
			if (!self::offsetExists($index)) {
			}

			return $this;
		}

		/**
		 * Apply the new value into current array value.
		 *
		 * @param mixed $values     The new value set
		 * @param bool  $existsOnly Set true to only apply on existing key
		 *
		 * @return self Chainable
		 */
		public function apply($values, bool $existsOnly = false)
		{
			if (is_iterable($values)) {
				foreach ($values as $key => $value) {
					if (!$existsOnly || self::offsetExists($key)) {
						$this[$key] = $value;
					}
				}
			}

			return true;
		}
	}
}
