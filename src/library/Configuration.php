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
  class Configuration
  {
  	private $config         = [];
  	private $configFilePath = '';
  	private $module;

  	public function __construct(ModulePackage $module, string $filename)
  	{
  		$configFolder = SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'configuration' . \DIRECTORY_SEPARATOR . $module->getCode() . \DIRECTORY_SEPARATOR;
  		if (!file_exists($configFolder)) {
  			mkdir($configFolder);
  		} elseif (!is_dir($configFolder)) {
  			new ThrowError('1001', 'Configuration', $configFolder . ' is not a directory.');
  		}

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

  			$this->config = require $this->configFilePath;
  		}
  	}

  	public function commit()
  	{
  		if (!($handle = fopen($this->configFilePath, 'w'))) {
  			new ThrowError('1003', 'Configuration', 'Cannot open file: ' . $this->configFilePath);
  		}

  		fwrite($handle, "<?php\nreturn " . var_export([], true) . ";\n?>");
  		fclose($handle);

  		return $this;
  	}
  }
}
