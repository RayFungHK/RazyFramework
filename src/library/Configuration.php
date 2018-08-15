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
  	private $config     = [];
  	private $configPath = '';
  	private $module;

  	public function __construct(ModulePackage $module)
  	{
  		$this->configPath = SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'configuration' . \DIRECTORY_SEPARATOR . 'module.' . $module->getCode() . '.php';

  		if (!file_exists($this->configPath)) {
  			// If the config file does not exist, create a empty config file
  			$this->commit();
  		} else {
  			// If the config file path is a directory, throw an error
  			if (is_dir($this->configPath)) {
  				new ThrowError('1002', 'Configuration', $this->configPath . ' is not a valid config file.');
  			}

  			$this->config = require $this->configPath;
  		}
  	}

  	public function commit()
  	{
  		if (!($handle = fopen($this->configPath, 'w'))) {
  			new ThrowError('1001', 'Configuration', 'Cannot open file: ' . $this->configPath);
  		}

  		fwrite($handle, "<?php\nreturn " . var_export([], true) . ";\n?>");
  		fclose($handle);

  		return $this;
  	}
  }
}
