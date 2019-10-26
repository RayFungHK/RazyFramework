<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework
{
	/**
	 * Emitter is a bridge used to connect between Manager and Package. It allows the both Class to access the private method to prevent anonymous class hijacking.
	 */
	class Wrapper
	{
		/**
		 * An array contains the Manager method and the emitter closure.
		 *
		 * @var array
		 */
		private $tunnels = [];

		/**
		 * An array contains the preset argument.
		 *
		 * @var array
		 */
		private $preset = [];

		/**
		 * An array contains the grant access.
		 *
		 * @var array
		 */
		private $grantaccess = [];

		/**
		 * An array contains the exchange Wrapper object.
		 *
		 * @var array
		 */
		private $exchanges = [];

		/**
		 * Magic method call, execute the wrapper method.
		 *
		 * @param string $name The method name
		 * @param array  $args An array contains the arguments
		 *
		 * @return mixed The result returned by method
		 */
		public function __call(string $name, array $args)
		{
			if (isset($this->tunnels[$name])) {
				if (!\array_key_exists($name, $this->grantaccess)) {
					$reflectionFunction = new \ReflectionFunction($this->tunnels[$name]);
					$reflectionClass    = $reflectionFunction->getClosureScopeClass();
					if (\in_array('RazyFramework\Injector', $reflectionClass->getTraitNames(), true)) {
						$this->grantaccess[$name] = true;
					}
				}

				if (!$this->grantaccess[$name]) {
					throw new ErrorHandler('You cannot wrap the class method that the class does not use the Injector.');
				}

				return \call_user_func_array($this->tunnels[$name], array_merge($this->preset, $args));
			}

			trigger_error('Call to undefined method ' . __CLASS__ . '::' . $name . '()', E_USER_ERROR);
		}

		/**
		 * The magic method invoke to access the exchange Wrapper.
		 *
		 * @param string $name The exchange name
		 *
		 * @return Wrapper The exchange Wrapper
		 */
		public function __invoke(string $name)
		{
			if (isset($this->exchange[$name])) {
				return $this->exchange[$name];
			}

			return null;
		}

		/**
		 * Set the preset the arguments.
		 *
		 * @param array ...$preset An array contains preset arguments
		 *
		 * @return self Chainable
		 */
		public function preset(...$preset)
		{
			$this->preset = $preset;

			return $this;
		}

		/**
		 * Create a method tunnel.
		 *
		 * @param string   $name     The name of the method in wrapper
		 * @param callback $callback The callable closure
		 *
		 * @return self Chainable
		 */
		public function bindTunnel(string $name, callable $callback)
		{
			if (!preg_match('/^[a-z_]\w*$/', $name)) {
				throw new ErrorHandler('The method name ' . $name . ' is not in a correct format.');
			}
			$this->tunnels[$name] = $callback;

			return $this;
		}

		/**
		 * Pass all bound tunnel to given callback closure.
		 *
		 * @param callback $callback The callable closure
		 *
		 * @return self Chainable
		 */
		public function walk(callable $callback)
		{
			foreach ($this->tunnels as $method => $closure) {
				\call_user_func($callback, $method, $closure);
			}

			return $this;
		}

		/**
		 * Bind another Wrapper for exchange call.
		 *
		 * @param string  $name    The exchange name
		 * @param Wrapper $wrapper The Wrapper object
		 *
		 * @return self Chainable
		 */
		public function exchange(string $name, self $wrapper)
		{
			if (!isset($this->exchange[$name])) {
				$this->exchange[$name] = $wrapper;
			}

			return $this;
		}
	}
}
