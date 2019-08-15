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
   * A plugin handler to allow the class obtain the plugin closure from the pool.
   */
  class Plugin
  {
  	/**
  	 * An array contains the plugin pool.
  	 *
  	 * @var array
  	 */
  	public static $pool = [];
  	/**
  	 * An array contains the plugin folder.
  	 *
  	 * @var array
  	 */
  	private $pluginFolder = [];

  	/**
  	 * An array contains plugins closure.
  	 *
  	 * @var array
  	 */
  	private $plugins = [];

    /**
     * Plugin constructor
     * @param mixed $object The hash name or the object which is going to create a plugin pool
     */
  	public function __construct($object)
  	{
  		if (is_scalar($object)) {
  			$hash = $object;
  		} elseif (!is_object($object)) {
  			$hash = spl_object_hash(new \stdClass());
  		} else {
  			$hash = spl_object_hash($object);
  		}

  		if (!isset(self::$pool[$hash])) {
  			$this->hash = $hash;
  			self::$pool[$hash] = $this;
  		} else {
  			throw new ErrorHandler('Duplicated plugin pool hash ' . $hash . '.');
  		}
  	}

    /**
     * Get the plugin by given hash or object
     * @param self $object The Plugin object
     */
  	public static function GetPool($object)
  	{
  		if (!is_object()) {
  			$hash = spl_object_hash(new \stdClass());
  		} else {
  			$hash = spl_object_hash($object);
  		}

  		if (isset(self::$pool[$hash])) {
  			return self::$pool[$hash];
  		}

  		return new self($object);
  	}

  	/**
  	 * Load the plugin closure from root plugin folder or package plugin folder.
  	 *
  	 * @param string $filename The plugin file name
  	 *
  	 * @return null|\Closure The plugin closure
  	 */
  	public function plugin(string $filename)
  	{
  		if (!isset($this->plugins[$filename])) {
        $this->plugins[$filename] = null;
  			foreach ($this->pluginFolder as $folder) {
  				$pluginFile = append($folder, $filename . '.php');

  				if (is_file($pluginFile)) {
  					$callback = require $pluginFile;
  					if (is_callable($callback)) {
  						$this->plugins[$filename] = $callback;

  						return $callback;
  					}
  				}
  			}
      }

  		return $this->plugins[$filename];
  	}

  	/**
  	 * Add a plugin folder for autoload.
  	 *
  	 * @param string $path The path of plugin folder
  	 */
  	public function addPluginFolder(string $path)
  	{
  		$path                 = tidy($path);
  		$this->pluginFolder[] = $path;

  		return $this;
  	}
  }
}
