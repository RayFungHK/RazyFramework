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
  use RazyFramework\RegexHelper;

  /**
   * Template block contains the structure, such as its sub block, raw text and parameters.
   */
  class Block
  {
  	/**
  	 * The Source object.
  	 *
  	 * @var Source
  	 */
  	private $source;

  	/**
  	 * The block name.
  	 *
  	 * @var string
  	 */
  	private $blockName = '';

  	/**
  	 * The block path.
  	 *
  	 * @var string
  	 */
  	private $path = '';

  	/**
  	 * The complete structure of the Block object.
  	 *
  	 * @var array
  	 */
  	private $structure = [];

  	/**
  	 * An array contains the sub blocks.
  	 *
  	 * @var array
  	 */
  	private $blocks = [];

  	/**
  	 * An array contains the block parameters.
  	 *
  	 * @var array
  	 */
  	private $parameters = [];

  	/**
  	 * The parent block.
  	 *
  	 * @var Block
  	 */
  	private $parent;

  	/**
  	 * Block constructor.
  	 *
  	 * @param string    $blockName The block name
  	 * @param array     &$content  An array contains the template content
  	 * @param Source    $source    The Source object
  	 * @param null|self $parent    Current block parent
  	 */
  	public function __construct(string $blockName, array &$content, Source $source, self $parent = null)
  	{
  		$this->source    = $source;
  		$this->blockName = $blockName;
  		$this->parent    = $parent;

  		if (!$parent) {
  			$this->path = '/';
  		} else {
  			$this->path = $parent->getPath() . '/' . $blockName;
  		}

  		$concat = '';
  		while ($line = array_shift($content)) {
  			if (false !== strpos($line, '<!-- ')) {
  				if (preg_match('/^\s*<!-- (START|END|RECURSION) BLOCK: (\w[\w-]*) -->\s*$/', $line, $matches)) {
  					if ($concat) {
  						$this->structure[] = $concat;
  						$concat            = '';
  					}

  					if ('START' === $matches[1]) {
  						if (isset($this->structure[$matches[2]])) {
  							throw new ErrorHandler('The block ' . $this->path . '/' . $matches[2] . ' is already exists.');
  						}

  						$this->blocks[$matches[2]]    = new self($matches[2], $content, $this->source, $this);
  						$this->structure[$matches[2]] = $this->blocks[$matches[2]];
  					} elseif ('RECURSION' === $matches[1]) {
  						if (!($parent = $this->getParentByName($matches[2]))) {
  							throw new ErrorHandler('The parent block ' . $matches[2] . ' is not found to become the recursive block.');
  						}

  						$this->blocks[$matches[2]]    = $parent;
  						$this->structure[$matches[2]] = $this->blocks[$matches[2]];
  					} elseif ('END' === $matches[1]) {
  						if ($blockName === $matches[2]) {
  							break;
  						}

  						throw new ErrorHandler('The block ' . $matches[2] . ' does not have the START tag. Current block [' . $blockName . ']');
  					}

  					continue;
  				}
  			}

  			if (!$regex = RegexHelper::GetCache('parameter-declare')) {
  				$regex = new RegexHelper('/\{\$(\w+)((?:\[(?:\d+|\w+|(?<quoteAry>[\'"])((?:(?!\k<quoteAry>)[^\\\\\\\\]|\\\\.)*)\k<quoteAry>)?\])*)\s*:\s*(?:(true|false)|(-?\d+(?:\.\d+)?)|(?<quote>[\'"])((?:(?!\k<quote>)[^\\\\\\\\]|\\\\.)*)\k<quote>)\}/', 'parameter-declare');
  			}

  			if ($matches = $regex->match($line)) {
  				$value = null;
  				if (isset($matches[8])) {
  					$value = $matches[8];
  				} elseif (isset($matches[6])) {
  					$value = (float) $matches[6];
  				} else {
  					$value = 'true' === $matches[5];
  				}
  				$this->declare($matches[1], $matches[2], $value);

  				continue;
  			}

  			$concat .= $line;
  		}

  		if ($concat) {
  			$this->structure[] = $concat;
  			$concat            = '';
  		}
  	}

  	/**
  	 * Get the parent entity.
  	 *
  	 * @return Entity The parent Entity object
  	 */
  	public function getParent()
  	{
  		return $this->parent;
  	}

  	/**
  	 * Return the block by given name.
  	 *
  	 * @param string $name The block name
  	 *
  	 * @return Block The Block object
  	 */
  	public function getBlock(string $name)
  	{
  		$name = trim($name);
  		if (!$this->hasBlock($name)) {
  			throw new ErrorHandler('Block ' . $name . ' is not exists in ' . $this->getPath() . '.');
  		}

  		return $this->blocks[$name];
  	}

  	/**
  	 * Determine the block is exists in current block.
  	 *
  	 * @param string $name The block name
  	 *
  	 * @return bool Return true if the block is exists
  	 */
  	public function hasBlock(string $name)
  	{
  		return isset($this->blocks[$name]);
  	}

  	/**
  	 * Return the processed block structure.
  	 *
  	 * @return array An array contains the block structure
  	 */
  	public function getStructure()
  	{
  		return $this->structure;
  	}

  	/**
  	 * Get the block name.
  	 *
  	 * @return string The block name
  	 */
  	public function getName()
  	{
  		return $this->blockName;
  	}

  	/**
  	 * Return the Source object.
  	 *
  	 * @return Source The Source object
  	 */
  	public function getSource()
  	{
  		return $this->source;
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
  		return \array_key_exists($parameter, $this->parameters);
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

  		return $this->source->recursion($parameter);
  	}

  	/**
  	 * Assign the block level parameter value.
  	 *
  	 * @param mixed $parameter The parameter name or an array of parameters
  	 * @param mixed $value     The parameter value
  	 *
  	 * @return self Chainable
  	 */
  	public function assign($parameter, $value = null)
  	{
  		if (\is_array($parameter)) {
  			foreach ($parameter as $index => $value) {
  				$this->assign($index, $value);
  			}
  		} else {
  			if (\is_object($value) && ($value instanceof \Closure)) {
  				// If the value is closure, pass the current value to closure
  				$this->parameters[$parameter] = $value($this->parameters[$parameter] ?? null);
  			} else {
  				$this->parameters[$parameter] = $value;
  			}
  		}

  		return $this;
  	}

  	/**
  	 * Get the block path.
  	 *
  	 * @return string The block path
  	 */
  	public function getPath()
  	{
  		return $this->path;
  	}

  	/**
  	 * Get the manager object recursively.
  	 *
  	 * @return Manager The Manager object
  	 */
  	public function getManager()
  	{
  		return $this->source->getManager();
  	}

  	/**
  	 * Walk through the parent and return the block by given block name.
  	 *
  	 * @param string $block the block name
  	 *
  	 * @return Block The Block object
  	 */
  	public function getParentByName(string $block)
  	{
  		$block = trim($block);
  		if (!$this->parent || !$block) {
  			return null;
  		}

  		if ($this->parent->getName() === $block) {
  			return $this->parent;
  		}

  		return $this->parent->hasParent($block);
  	}

  	/**
  	 * Declare the parameter.
  	 *
  	 * @param string $name       The parameter name
  	 * @param string $arrayIndex A string contains the index of the parameter
  	 * @param mixed  $value      The value of the parameter
  	 *
  	 * @return self Chainable
  	 */
  	private function declare(string $name, string $arrayIndex, $value)
  	{
  		// If it is declaring a variable as an array
  		if ($arrayIndex) {
  			if (!$regex = RegexHelper::GetCache('parameter-declare-array')) {
  				$regex = new RegexHelper('/\[(\d+|\w+|(?<quoteAry>[\'"])((?:(?!\k<quoteAry>)[^\\\\\\\\]|\\\\.)*)\k<quoteAry>)?\]/', 'parameter-declare-array');
  			}

  			if (!isset($this->parameters[$name]) || !\is_array($this->parameters[$name])) {
  				$this->parameters[$name] = [];
  			}

  			$clips = $regex->extract($arrayIndex, function ($matches) {
  				return $matches[3] ?? $matches[1] ?? '';
  			});

  			$param = &$this->parameters[$name];
  			while (\count($clips)) {
  				$clip = array_shift($clips);
  				if (\count($clips)) {
  					if (!$clip) {
  						$param[] = [];
  						// Get the last value of the array by reference
  						end($param);
  						$param = &$param[key($param)];
  					} else {
  						if (!isset($param[$clip])) {
  							$param[$clip] = [];
  						}
  						$param = &$param[$clip];
  					}
  				} else {
  					if ($clip) {
  						$param[$clip] = $value;
  					} else {
  						$param[] = $value;
  					}
  				}
  			}
  		} else {
  			$this->parameters[$name] = $value;
  		}

  		return $this;
  	}
  }
}
