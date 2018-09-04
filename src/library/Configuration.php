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
  	private $configFile = '';
  	private $filename = '';
  	private $loaded         = false;
  	private $module;
  	private $iterator;

  	public function __construct(ModulePackage $module, string $filename)
  	{
      $distribution = str_replace('/', \DIRECTORY_SEPARATOR, ModuleManager::GetDistribution());
  		$this->configFile = SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'configuration' . $distribution . $module->getCode() . \DIRECTORY_SEPARATOR;

  		$this->filename = trim($filename);
  		if (!preg_match('/^[\w-]+/i', $this->filename)) {
  			new ThrowError('1001', 'Configuration', 'Config file name cannot be empty.');
  		}

      $this->configFile .= $this->filename . '.php';
  		if (file_exists($this->configFile)) {
  			// If the config file path is a directory, throw an error
  			if (is_dir($this->configFile)) {
  				new ThrowError('1002', 'Configuration', $this->configFile . ' is not a valid config file.');
  			}

  			// Pass the config array to parent constructor
  			parent::__construct(require $this->configFile);
  			$this->loaded   = true;
  			$this->iterator = $this->getIterator();
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
  		$pathParts = pathinfo($this->configFile);

  		// Check the configuration folder does exist
  		if (!file_exists($pathParts['dirname'])) {
  			// Create the directory
  			mkdir($pathParts['dirname'], 0755, true);
  		} elseif (!is_dir($pathParts['dirname'])) {
  			// If the path does exist but not a directory, throw an error
  			new ThrowError('1003', 'Configuration', $pathParts['dirname'] . ' is not a directory.');
  		}

  		if (!($handle = fopen($this->configFile, 'w'))) {
  			new ThrowError('1004', 'Configuration', 'Cannot open file: ' . $this->configFile);
  		}

  		fwrite($handle, "<?php\nreturn " . var_export($this->getArrayCopy(), true) . ";\n?>");
  		fclose($handle);

  		return $this;
  	}
  }
}
