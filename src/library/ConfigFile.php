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
   * An array-like object contains the configuration, you can export the config into a PHP file, a JSON file or a XML file.
   */
  class ConfigFile extends Iterator\Manager
  {
  	/**
  	 * The config file name.
  	 *
  	 * @var string
  	 */
  	private $filename = '';

  	/**
  	 * The config file extension.
  	 *
  	 * @var string
  	 */
  	private $extension = '';

  	/**
  	 * The config file path.
  	 *
  	 * @var string
  	 */
  	private $path = '';

  	/**
  	 * The config changed status, it will not write to any file if it is false.
  	 *
  	 * @var bool
  	 */
  	private $changed = false;

  	public function __construct(string $path)
  	{
  		$this->path = tidy($path);
  		$pathInfo   = pathinfo($path);

  		$this->filename  = trim($pathInfo['filename']);
  		$this->extension = strtolower($pathInfo['extension']);

  		if (!$this->filename) {
  			throw new ErrorHandler('Config file name cannot be empty.');
  		}

  		if (is_file($this->path)) {
  			// If the config file path is a directory, throw an error
  			if (is_dir($this->path)) {
  				throw new ErrorHandler('The config file' . $this->path . ' is not a valid config file.');
  			}

  			if ('php' === $this->extension) {
  				parent::__construct(require $this->path);
  			} elseif ('json' === $this->extension) {
  				$data = json_decode(file_get_contents($this->path), true);
  				parent::__construct($data);
  			} elseif ('ini' === $this->extension) {
  				$data = parse_ini_file($this->path, true);
  				parent::__construct($data);
  			}
  		}
  	}

  	/**
  	 * Save the config into the file.
  	 *
  	 * @return self Chainable
  	 */
  	public function save()
  	{
  		if (!$this->changed) {
  			return $this;
  		}

  		if ('php' === $this->extension) {
  			$this->saveAsPHP();
  		} elseif ('ini' === $this->extension) {
  			$this->saveAsINI();
  		} elseif ('json' === $this->extension) {
  			$this->saveAsJson();
  		}

  		return $this;
  	}

  	/**
  	 * Save the config file into a PHP file.
  	 *
  	 * @return self Chainable
  	 */
  	public function saveAsPHP()
  	{
  		if (!$this->changed) {
  			return $this;
  		}

  		$this->writeFile('<?php' . \PHP_EOL . 'return ' . var_export($this->getArrayCopy(), true) . ';' . \PHP_EOL . '?>');

  		return $this;
  	}

  	/**
  	 * Save the config file into an ini file.
  	 *
  	 * @return self Chainable
  	 */
  	public function saveAsINI()
  	{
  		if (!$this->changed) {
  			return $this;
  		}

  		$content = [];
  		foreach ($this->getArrayCopy() as $key => $val) {
  			if (is_array($val)) {
  				$content[] = '[' . $key . ']';
  				foreach ($val as $skey => $sval) {
  					$content[] = $skey . ' = ' . (is_numeric($sval) ? $sval : '"' . $sval . '"');
  				}
  			} else {
  				$content[] = $key . ' = ' . (is_numeric($val) ? $val : '"' . $val . '"');
  			}
  		}
  		$this->writeFile(implode(\PHP_EOL, $content));

  		return $this;
  	}

  	/**
  	 * Save the config file into a json file.
  	 *
  	 * @return self Chainable
  	 */
  	public function saveAsJSON()
  	{
  		if (!$this->changed) {
  			return $this;
  		}

  		$this->writeFile(json_encode($this->getArrayCopy()));

  		return $this;
  	}

  	/**
  	 * Overrides offsetSet method from \ArrayObject, pass the value to the rule closure before set the value.
  	 *
  	 * @param int|string $index The key of the iterator
  	 * @param mixed      $value The value to set to the iterator
  	 */
  	public function offsetSet($index, $value)
  	{
  		if (!isset($this[$index]) || $this[$index] !== $value) {
  			$this->changed = true;
  		}
  		parent::offsetSet($index, $value);
  	}

  	/**
  	 * Write the content to the file.
  	 *
  	 * @param string $content The config file content
  	 *
  	 * @return self Chainable
  	 */
  	private function writeFile(string $content)
  	{
  		// Get the config file path info
  		$pathInfo = pathinfo($this->path);

  		// Check the configuration folder does exist
  		if (!is_file($pathInfo['dirname'])) {
  			// Create the directory
  			mkdir($pathInfo['dirname'], 0755, true);
  		} elseif (!is_dir($pathInfo['dirname'])) {
  			// If the path does exist but not a directory, throw an error
  			throw new ErrorHandler($pathInfo['dirname'] . ' is not a directory.');
  		}

      file_put_contents($this->path, $content);
  		if ($handle = fopen($this->path, 'w')) {
				fwrite($handle, $content);

				fclose($handle);
  		}

  		return $this;
  	}
  }
}
