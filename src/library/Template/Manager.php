<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework\Template
{
	use RazyFramework\Plugin;

	/**
	 * Fast and easy-to-use template engine. You can control every block from template file and assign parameter for post-process output.
	 */
  class Manager
  {
  	use \RazyFramework\Injector;

  	/**
  	 * An array contains the source object.
  	 *
  	 * @var array
  	 */
  	private $sources    = [];
  	/**
  	 * An array contains the manager level parameter.
  	 *
  	 * @var array
  	 */
  	private $parameters = [];

  	/**
  	 * The Plugin object.
  	 *
  	 * @var Plugin
  	 */
  	private $plugin;

  	/**
  	 * Manager constructor.
  	 */
  	public function __construct()
  	{
  		$this->plugin = new Plugin($this);
  		$this->plugin->addPluginFolder(append(PLUGIN_FOLDER, 'Template'));
  	}

  	/**
  	 * Assign the manager level parameter value.
  	 *
  	 * @param mixed $parameter The parameter name or an array of parameters
  	 * @param mixed $value     The parameter value
  	 *
  	 * @return self Chainable
  	 */
  	public function assign($parameter, $value = null)
  	{
  		if (is_array($parameter)) {
  			foreach ($parameter as $index => $value) {
  				$this->assign($index, $value);
  			}
  		} else {
  			if (is_object($value) && ($value instanceof \Closure)) {
  				// If the value is closure, pass the current value to closure
  				$this->parameters[$parameter] = $value($this->parameters[$parameter] ?? null);
  			} else {
  				$this->parameters[$parameter] = $value;
  			}
  		}

  		return $this;
  	}

  	/**
  	 * Determine the parameter has been assigned.
  	 *
  	 * @param string $parameter The parameter name
  	 *
  	 * @return bool Return true if the parameter is exists
  	 */
  	public function hasValue(string $parameter)
  	{
  		return array_key_exists($parameter, $this->parameters);
  	}

  	/**
  	 * Return the parameter value.
  	 *
  	 * @param string $parameter The parameter name
  	 *
  	 * @return mixed The parameter value
  	 */
  	public function getValue(string $parameter)
  	{
  		return $this->parameters[$parameter] ?? null;
  	}

  	/**
  	 * Get the paramater value recursively.
  	 *
  	 * @param string $parameter The parameter name
  	 *
  	 * @return mixed The value returned by parameter list
  	 */
  	public function recursion(string $parameter)
  	{
  		if ($this->hasValue($parameter)) {
  			return $this->getValue($parameter);
  		}

  		return null;
  	}

  	/**
  	 * Load the template file and return as Source object.
  	 *
  	 * @param string $path The file path
  	 *
  	 * @return Source The Source object
  	 */
  	public function load(string $path)
  	{
  		$source                          = new Source($path, $this);
  		$this->sources[$source->getID()] = $source;

  		return $source;
  	}

  	/**
  	 * Return the entity content in queue list by given section name.
  	 *
  	 * @param array $sections An array contains section name
  	 */
  	public function outputQueued(array $sections)
  	{
  		$content = '';
  		foreach ($sections as $section) {
  			if (isset($this->queue[$section])) {
  				$content .= $this->queue[$section]->output();
  			}
  		}
  		$this->queue = [];

  		return $content;
  	}

  	/**
  	 * Get the plugin closure from the plugin pool.
  	 *
  	 * @param string $type The type of the plugin
  	 * @param string $name The plugin name
  	 *
  	 * @return null|\Closure The plugin closure
  	 */
  	public function plugin(string $type, string $name)
  	{
  		$identify = $type . '.' . $name;

  		return $this->plugin->plugin($identify);
  	}

  	/**
  	 * Add a source to queue list.
  	 *
  	 * @param Source $source The Source object
  	 * @param string $name   The queue name
  	 */
  	public function addQueue(Source $source, string $name)
  	{
  		$name = trim($name);
  		if (!$name) {
  			$name = sprintf('%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff));
  		}
  		$this->queue[$name] = $source;

  		return $this;
  	}
  }
}
