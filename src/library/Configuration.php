<?php

/*
 * This file is part of RazyFramwork.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework
{
  class Configuration extends \ArrayObject
  {
  	private $configFilePath = '';
  	private $loaded         = false;
  	private $module;
  	private $iterator;

  	public function __construct(ModulePackage $module, string $filename)
  	{
  		$configFolder = SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'configuration' . \DIRECTORY_SEPARATOR . $module->getCode() . \DIRECTORY_SEPARATOR;

  		$filename = trim($filename);
  		if (!preg_match('/^[\w-]+/i', $filename)) {
  			new ThrowError('1001', 'Configuration', 'Config file name cannot be empty.');
  		}

  		$this->configFilePath = $configFolder . $filename . '.php';

  		if (file_exists($this->configFilePath)) {
  			// If the config file path is a directory, throw an error
  			if (is_dir($this->configFilePath)) {
  				new ThrowError('1002', 'Configuration', $this->configFilePath . ' is not a valid config file.');
  			}

  			$config = require $this->configFilePath;

  			// Pass the config array to parent constructor
  			parent::__construct($config);
  			$this->loaded    = true;
  			$this->iterator  = $this->getIterator();
  		}
  	}

  	public function isLoaded()
  	{
  		return $this->loaded;
  	}

  	public function &offsetGet($index)
  	{
  		if ($this->offsetExists($index)) {
  			return $this->iterator[$index];
  		}

  		return null;
  	}

  	public function commit()
  	{
  		// Get the config file path info
  		$pathParts = pathinfo($this->configFilePath);

  		// Check the configuration folder does exist
  		if (!file_exists($pathParts['dirname'])) {
  			// Create the directory
  			mkdir($pathParts['dirname']);
  		} elseif (!is_dir($pathParts['dirname'])) {
  			// If the path does exist but not a directory, throw an error
  			new ThrowError('1003', 'Configuration', $pathParts['dirname'] . ' is not a directory.');
  		}

  		if (!($handle = fopen($this->configFilePath, 'w'))) {
  			new ThrowError('1004', 'Configuration', 'Cannot open file: ' . $this->configFilePath);
  		}

  		fwrite($handle, "<?php\nreturn " . var_export($this->getArrayCopy(), true) . ";\n?>");
  		fclose($handle);

  		return $this;
  	}
  }
}
