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
  use RazyFramework\ErrorHandler;
  use RazyFramework\Wrapper;

  /**
   * Template Source is an object continas the file structure and its parameters.
   */
  class Source
  {
  	/**
  	 * An array contains a queue of Source object used to output.
  	 *
  	 * @var array
  	 */
  	private static $queue = [];

  	/**
  	 * The root Entity object.
  	 *
  	 * @var Entity
  	 */
  	private $root = [];

  	/**
  	 * An array contains the Source parameters.
  	 *
  	 * @var array
  	 */
  	private $parameters = [];

  	/**
  	 * The Manager unique ID.
  	 *
  	 * @var string
  	 */
  	private $id = '';

  	/**
  	 * The Manager object.
  	 *
  	 * @var Manager
  	 */
  	private $manager;

  	/**
  	 * Template Source constructor.
  	 *
  	 * @param string  $tplPath The path of template file
  	 * @param Manager $manager The Manager object
  	 */
  	public function __construct(string $tplPath, Manager $manager)
  	{
  		if (!is_file($tplPath)) {
  			throw new ErrorHandler('Template file ' . $tplPath . ' is not exists.');
  		}
  		$content       = file($tplPath);
  		$this->manager = $manager;

  		$this->root = new Entity(new Block('_ROOT', $content, $this));
  		$this->id   = sprintf('%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff));
  	}

  	/**
  	 * Assign the source level parameter value.
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

  		return $this->manager->recursion($parameter);
  	}

  	/**
  	 * Get the Manager ID.
  	 *
  	 * @return string The Manager ID
  	 */
  	public function getID()
  	{
  		return $this->id;
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
  	 * Get the manager object recursively.
  	 *
  	 * @return Manager The Manager object
  	 */
  	public function getManager()
  	{
  		return $this->manager;
  	}

  	/**
  	 * Add current template source into queue list.
  	 *
  	 * @param string $name The section name
  	 */
  	public function queue(string $name = '')
  	{
  		$this->manager->addQueue($this, $name);

  		return $this;
  	}

  	/**
  	 * Get the root entity.
  	 *
  	 * @return Entity The root entity object
  	 */
  	public function getRootBlock()
  	{
  		return $this->root;
  	}

  	/**
  	 * Return the entity content.
  	 *
  	 * @return string The entity content
  	 */
  	public function output()
  	{
  		return $this->root->process();
  	}
  }
}
