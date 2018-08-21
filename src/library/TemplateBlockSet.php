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
  class TemplateBlockSet extends \ArrayObject
  {
  	private static $dynamicFilters = [];
  	private static $filters        = [];

  	public function __construct($blockList)
  	{
  		parent::__construct((is_array($blockList)) ? $blockList : [$blockList]);
  	}

  	public function find($selector)
  	{
  		// Trim selector, remove repeatly slash
  		$selector = trim(preg_replace('/\/+/', '/', $selector . '/'));
  		if (preg_match('/^(?:\w+(?:\[\w+(?:=[\^*$|]?(?:\w+|"(?:[^"\\\\]+|\\\\.)*"))?\])*(?::[\w-]+(?:\((?:"(?:[^"\\\\]+|\\\\.)*"|[^()]+)\))*)*\/)+$/', $selector)) {
  			// Get the current level block list
  			$blockList = $this->getArrayCopy();

  			// Remove the first & last slash
  			$selector = trim($selector, '/');

  			// Extract Block Path
  			$pathClips = explode('/', $selector);
  			if (count($pathClips)) {
  				foreach ($pathClips as $path) {
  					// Extract condition and filter function tag
  					$pathCount = preg_match('/(\w+)((?:\[\w+(?:(?:!=|=[\^*$|]?)(?:\w+|"(?:[^"\\\\]+|\\\\.)*"))?\])*)((?::[\w-]+(?:\((?:"(?:[^"\\\\]+|\\\\.)*"|[^()]+)\))?)*)/', $path, $clip);

  					$blockName = $clip[1];
  					$selectors = [];
  					$filters   = [];

  					// Extract variable selector
  					if (isset($clip[2])) {
  						if (preg_match_all('/\[(\w+)(?:(!=|=[\^*$|]?)(?|(\w+)|"(?:([^"\\\\]+|\\\\.)*)"))?\]/', $clip[2], $matches, PREG_SET_ORDER)) {
  							foreach ($matches as $selector) {
  								$selectors[] = $selector;
  							}
  						}
  					}

  					// Extract filter function
  					if (isset($clip[3])) {
  						if (preg_match_all('/:([\w-]+)(?:\((?|"(?:([^"\\\\]+|\\\\.)*)"|([^()]+))\))?/i', $clip[3], $matches, PREG_SET_ORDER)) {
  							foreach ($matches as $filter) {
  								$filters[] = $filter;
  							}
  						}
  					}

  					// Get all next level block from block list
  					$blockCollection = [];
  					if (count($blockList)) {
  						foreach ($blockList as $block) {
  							if ($block->hasBlock($blockName)) {
  								$blockCollection = array_merge($blockCollection, $block->getBlockList($blockName));
  							}
  						}
  					}

  					// If filter function tag was found, start filtering
  					// Arguments: $index, $block, $source, $arg
  					if (count($blockCollection) && count($filters)) {
  						// Clone the current block list
  						$source = $blockCollection;

  						foreach ($blockCollection as $index => $block) {
  							// Declare the object
  							$bindObject = (object) [
  								'index'           => $index,
  								'block'           => $block,
  								'blockCollection' => $source,
  								'length'          => count($source),
  								'parameter'       => null,
  							];

  							foreach ($filters as $filter) {
  								if (self::GetFilter('filter.' . $filter[1])) {
  									if (isset($functionFilter[2])) {
  										// If the parameter quoted by double quote, the string with backslashes
  										// that recognized by C-like \n, \r ..., octal and hexadecimal representation will be stripped off
  										$bindObject->parameter = stripcslashes($filter[2]);
  									}

  									// If filter function return false, remove current block from the list
  									if (!self::CallFilter('filter.' . $filter[1], $bindObject)) {
  										unset($blockCollection[$index]);

  										break;
  									}
  								}
  							}
  						}
  					}

  					if (count($blockCollection) && count($selectors)) {
  						foreach ($blockCollection as $index => $block) {
  							foreach ($selectors as $selector) {
  								$tagName = $selector[1];

  								// Check the value is defined & not empty
  								if ($block->hasVariable($tagName) && $block->getVariable($tagName)) {
  									$valueDefined = true;
  								}

  								// If operator exists, start condition filter
  								if (isset($selector[3])) {
  									$operator   = $selector[2];
  									$comparison = stripcslashes($selector[3]);
  									$value      = $block->getVariable($tagName);

  									if ((
					  is_string($value) && (
										// Equal
										('=' === $operator && $comparison !== $value) ||
										// Not Equal
										('!=' === $operator && $comparison === $value) ||
										// Contain
										('=*' === $operator && false === strpos($value, $comparison)) ||
										// Start With
										('=^' === $operator && substr($value, 0, strlen($comparison)) !== $comparison) ||
										// End With
										('=$' === $operator && substr($value, -strlen($comparison)) !== $comparison)
									  )
					) || (
										is_array($value) &&
									  // Element in List
									  ('=|' === $operator && !in_array($comparison, $value, true))
									)) {
  										unset($blockCollection[$index]);

  										break;
  									}
  								}
  							}
  						}
  					}

  					$blockList = $blockCollection;

  					if (0 === count($blockCollection)) {
  						break;
  					}
  				}

  				return new self($blockCollection);
  			}
  		} else {
  			new ThrowError('TemplateBlockSet', '1001', 'Invalid selector');
  		}
  	}

  	public function each($callback)
  	{
  		if (is_callable($callback)) {
  			if (count($this)) {
  				foreach ($this as $block) {
  					call_user_func($callback->bindTo($block));
  				}
  			}
  		} else {
  			new ThrowError('TemplateBlockSet', '2001', 'Invalid callback function for each method');
  		}

  		return $this;
  	}

  	public function assign($variable, $value = null)
  	{
  		if (count($this)) {
  			foreach ($this as $block) {
  				$block->assign($variable, $value);
  			}
  		}

  		return $this;
  	}

  	public function filter($callback)
  	{
  		if (is_callable($callback)) {
  			if (count($this)) {
  				foreach ($this as $index => $block) {
  					if (!call_user_func($callback->bindTo($block))) {
  						unset($this[$index]);
  					}
  				}
  			}
  		} else {
  			new ThrowError('TemplateBlockSet', '2001', 'Invalid callback function for each method');
  		}

  		return $this;
  	}

  	public static function CreateFilter(string $name, callable $callback)
  	{
  		$name = trim($name);
  		if (preg_match('/^[\w-]+$/', $name)) {
  			$filter = 'filter.' . $name;
  			if (!isset(self::$filters[$filter])) {
  				self::$dynamicFilters[$filter] = null;
  			}

  			if (is_callable($callback)) {
  				self::$dynamicFilters[$filter] = $callback;
  			}
  		}
  	}

  	private static function GetFilter($filter)
  	{
  		if (!array_key_exists($filter, self::$filters)) {
  			self::$filters[$filter] = null;

  			$pluginFile = __DIR__ . \DIRECTORY_SEPARATOR . 'tpl_plugins' . \DIRECTORY_SEPARATOR . $filter . '.php';
  			if (file_exists($pluginFile)) {
  				$callback = require $pluginFile;
  				if (is_callable($callback)) {
  					self::$filters[$filter] = $callback;

  					return $callback;
  				}
  			}
  		}

  		if (array_key_exists($filter, self::$dynamicFilters)) {
  			return self::$dynamicFilters[$filter];
  		}

  		return self::$filters[$filter];
  	}

  	private static function CallFilter($filterName, $bindObject)
  	{
  		if (!($filter = self::GetFilter($filterName))) {
  			new ThrowError('TemplateBlockSet', '3001', 'Cannot load [' . $filterName . '] filter function.');
  		}

  		return call_user_func(\Closure::bind($filter, $bindObject), $bindObject->parameter);
  	}
  }
}
