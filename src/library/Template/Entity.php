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
  use RazyFramework\RegexHelper;

  /**
   * Template entity will be processed in output. Except the root entity, you can create any number of entity to list a bunch of data. Every entity contains its paramater, to allow front end developer use for.
   */
  class Entity
  {
  	/**
  	 * The Block object.
  	 *
  	 * @var Block
  	 */
  	private $block;

  	/**
  	 * The parent entity.
  	 *
  	 * @var Entity
  	 */
  	private $parent;

  	/**
  	 * The entity id.
  	 *
  	 * @var string
  	 */
  	private $id = '';

  	/**
  	 * An array contains the entity parameters.
  	 *
  	 * @var array
  	 */
  	private $parameters = [];

  	/**
  	 * An array contains the sub entity under current entity.
  	 *
  	 * @var array
  	 */
  	private $entities = [];

  	private $output = [];

  	/**
  	 * Entity constructor.
  	 *
  	 * @param Block     $block  The Block object
  	 * @param string    $id     The entity id
  	 * @param null|self $parent The parent Entity object
  	 */
  	public function __construct(Block $block, string $id = '', self $parent = null)
  	{
  		$this->block  = $block;
  		$this->parent = $parent;
  		$id           = trim($id);
  		if (!$id) {
  			$id = sprintf('%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff));
  		}
  		$this->id = $id;
  	}

  	/**
  	 * Return the entity id.
  	 *
  	 * @return string The entity id
  	 */
  	public function getID()
  	{
  		return $this->id;
  	}

  	/**
  	 * Detach this entity from the list.
  	 *
  	 * @return self Chainable
  	 */
  	public function detach()
  	{
  		$this->parent->remove($this->block->getName(), $this->id);

  		return $this;
  	}

  	/**
  	 * Remove the entity by given block name and entity id.
  	 *
  	 * @param string $blockName The block name under current block level
  	 * @param string $id        The entity id
  	 *
  	 * @return self Chainable
  	 */
  	public function remove(string $blockName, string $id)
  	{
  		if (isset($this->entities[$blockName])) {
  			unset($this->entities[$blockName][$id]);
  		}

  		return $this;
  	}

  	/**
  	 * Create a new block or return the Entity object by the id if it is created.
  	 *
  	 * @param string $blockName The block name under current block level
  	 * @param string $id        The entity id
  	 *
  	 * @return Entity The Entity object
  	 */
  	public function newBlock(string $blockName, string $id = '')
  	{
  		$id = trim($id);
  		if (!$id) {
  			$id = sprintf('%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff));
  		}

  		if (isset($this->entities[$blockName][$id])) {
  			return $this->entities[$blockName][$id];
  		}

  		$block = $this->block->getBlock($blockName);
  		if (!isset($this->entities[$blockName])) {
  			$this->entities[$blockName] = [];
  		}
  		$blockEntity                     = new self($block, $id, $this);
  		$this->entities[$blockName][$id] = $blockEntity;

  		return $blockEntity;
  	}

  	/**
  	 * Assign the entity level parameter value.
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
  			if (\is_object($value) && $value instanceof \Closure) {
  				// If the value is closure, pass the current value to closure
  				$this->parameters[$parameter] = $value($this->parameters[$parameter] ?? null);
  			} else {
  				$this->parameters[$parameter] = $value;
  			}
  		}

  		return $this;
  	}

  	/**
  	 * Process and return the block content and the parameter tag and function tag will be replaced.
  	 *
  	 * @return string The processed block content
  	 */
  	public function process()
  	{
  		$content   = '';
  		$structure = $this->block->getStructure();
  		foreach ($structure as $index => $entity) {
  			if ($entity instanceof Block) {
  				$content .= $this->processEntity($index);
  			} else {
  				$clip = $entity;
  				$content .= $this->replaceTag($clip);
  			}
  		}

  		return $content;
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
  		return ($this->hasValue($parameter)) ? $this->parameters[$parameter] : null;
  	}

  	/**
  	 * Return the count of entity by block name.
  	 *
  	 * @param string $blockName The block name
  	 *
  	 * @return int The count of entity
  	 */
  	public function getBlockCount(string $blockName)
  	{
  		if (isset($this->entities[$blockName])) {
  			return \count($this->entities[$blockName]);
  		}

  		return 0;
  	}

  	/**
  	 * Find the entities by given path.
  	 *
  	 * @param string $path The block path
  	 *
  	 * @return array An array contains matched entity
  	 */
  	public function find(string $path)
  	{
  		if (!$regex = RegexHelper::GetCache('template-find-path')) {
  			$regex = new RegexHelper('/\/?(\w+)(?:\[(?:(\d+)|(?<quote>[\'"])((?:(?!\k<quote>)[^\\\\\\\\]|\\\\.)+)\k<quote>)\])?/', 'template-find-path');
  		}

  		// Check the path is valid
  		if ($regex->combination($path)) {
  			$result    = [$this];
  			$pathClips = $regex->extract($path);

  			// Find the entity by every path deeply
  			foreach ($pathClips as $clip) {
  				$blockName   = $clip[1];
  				$entityFound = [];

  				// Search the entity in the entity list
  				foreach ($result as $entity) {
  					if ($entities = $entity->getEntities($blockName)) {
  						if (\count($clip) > 2 && $filter = isset($clip[4]) ? $clip[4] : $clip[2]) {
  							// Convert the non-escaped wildcard
  							if (!$regex = RegexHelper::GetCache('template-wildcard')) {
  								$regex = new RegexHelper('/(?<!\\\\)(?:\\\\\\\\)*\\*/', 'template-wildcard');
  							}
  							$filter = '/^' . $regex->replace('.*?', $filter) . '$/';

  							// If the id is given, filter the matched entity list
  							foreach ($entities as $id => $entity) {
  								if (preg_match($filter, $id)) {
  									$entityFound[] = $entity;
  								}
  							}
  						} else {
  							// Merge all matched entity list to current entity list
  							$entityFound = array_merge($entityFound, $entities);
  						}
  					}
  				}

  				if (!\count($entityFound)) {
  					return [];
  				}

  				$result = $entityFound;
  			}

  			return $result;
  		}

  		return [];
  	}

  	/**
  	 * Get the current entity block name.
  	 *
  	 * @return string The block name
  	 */
  	public function getBlockName()
  	{
  		return $this->block->getName();
  	}

  	/**
  	 * Get the entity list by the given block name.
  	 *
  	 * @param string $blockName [description]
  	 *
  	 * @return [type] [description]
  	 */
  	public function getEntities(string $blockName)
  	{
  		return $this->entities[$blockName] ?? [];
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

  		return $this->block->recursion($parameter);
  	}

  	/**
  	 * Get the manager object recursively.
  	 *
  	 * @return Manager The Manager object
  	 */
  	public function getManager()
  	{
  		return $this->block->getManager();
  	}

  	/**
  	 * Convert the argument string into an array.
  	 *
  	 * @param string $argumentString The string ready to convert to an array
  	 *
  	 * @return array An array contains arguments
  	 */
  	private function processArguments(string $argumentString)
  	{
  		if (!$regex = RegexHelper::GetCache('template-arguments')) {
  			$regex = new RegexHelper('/:(true|false|(-?\d+(?:\.\d+)?)|(?<quote>[\'"])((?:(?!\k<quote>)[^\\\\\\\\]|\\\\.)*)\k<quote>)/', 'template-arguments');
  		}

  		return $regex->extract($argumentString, function ($matches) {
  			if (isset($matches[4])) {
  				$value = stripcslashes($matches[4]);
  				// Replace the paramater tag or function tag
  				return $this->replaceTag($value);
  			}

  			if ($matches[2]) {
  				// Convert the numeric string to float
  				return (float) $matches[2];
  			}

  			return ('true' === $matches[1]) ? true : false;
  		});
  	}

  	/**
  	 * Pass the value to modifier.
  	 *
  	 * @param mixed  $value          The parameter value
  	 * @param string $modifierString The string of modifier in parameter tag
  	 *
  	 * @return mixed $value          The processed parameter value
  	 */
  	private function processModifier($value, string $modifierString)
  	{
  		if ($modifierString) {
  			if (!$regex = RegexHelper::GetCache('template-split-modifier')) {
  				$regex = new RegexHelper('/\|/', 'template-split-modifier');
  				$regex->exclude(RegexHelper::EXCLUDE_DOUBLE_QUOTE | RegexHelper::EXCLUDE_SINGLE_QUOTE);
  			}

  			$clips = $regex->split(substr($modifierString, 1));
  			foreach ($clips as $clip) {
  				if (!$regex = RegexHelper::GetCache('template-modifier')) {
  					$regex = new RegexHelper('/^(\w+)((?::(true|false|-?\d+(?:\.\d+)?|(?<quote>[\'"])(?:(?!\k<quote>)[^\\\\\\\\]|\\\\.)*\k<quote>)*)*)$/', 'template-modifier');
  				}

  				if ($matches = $regex->match($clip)) {
  					$modifier = $this->block->getManager()->plugin('modifier', $matches[1]);
  					$object   = (object) [
  						'value'     => $value,
  						'arguments' => $this->processArguments($matches[2]),
  					];

  					if ($modifier) {
  						$modifier = $modifier->bindTo($object);
  						$value    = \call_user_func_array($modifier, $object->arguments);
  					}
  				} else {
  					// If the modifier arguments format is not valid, return null
  					return null;
  				}
  			}
  		}

  		return $value;
  	}

  	/**
  	 * Get the parameter value from different level, if the content is wrapped with the
  	 * closing tag, show the wrapped content if the value is true.
  	 *
  	 * @param string $parameter The paramater name
  	 * @param string $path      The array path
  	 *
  	 * @return string The paramater value or wrapped content
  	 */
  	private function processParameter(string $parameter, string $path)
  	{
  		if (!$regex = RegexHelper::GetCache('template-split-dot')) {
  			$regex = new RegexHelper('/\./', 'template-split-dot');
  			$regex->exclude(RegexHelper::EXCLUDE_DOUBLE_QUOTE | RegexHelper::EXCLUDE_SINGLE_QUOTE);
  		}

  		$clips = [];
  		if ($path) {
  			$clips = $regex->split($path);
  		}

  		$value = $this->recursion($parameter);

  		// If the value is not empty
  		if (is_iterable($value)) {
  			// If the parameter tag contains array path, walk the array and get the value
  			if (\count($clips)) {
  				while ($clip = array_shift($clips)) {
  					// If the value is not an array, return empty string
  					if (!is_iterable($value)) {
  						return null;
  					}

  					$index = false;
  					if (preg_match('/(.+)\[(\d+)\]$/', $clip, $matches)) {
  						$index = $matches[2];
  						$clip  = $matches[1];
  					}

  					$clip = $this->unquote($clip);
  					if (\array_key_exists($clip, $value)) {
  						$value = $value[$clip];
  						if (false !== $index) {
  							if (is_iterable($value) && \count($value) > $index) {
  								$value = \array_slice($value, $index, 1);
  							} else {
  								return null;
  							}
  						}
  					} else {
  						return null;
  					}
  				}
  			}

  			return $value;
  		}

  		return $value;
  	}

  	/**
  	 * Compare the value with the given string.
  	 *
  	 * @param string $value      The value of parameter
  	 * @param string $comparison The value to compare
  	 *
  	 * @return bool Return true if matched
  	 */
  	private function comparision(string $value, string $comparison)
  	{
  		if (!$regex = RegexHelper::GetCache('template-comparison')) {
  			$regex = new RegexHelper('/^\s*((?:[!*$^<>])?=|[><])\s*(.+)$/', 'template-comparison');
  		}
  		$matches = $regex->match($comparison);

  		$operand = $this->unquote($matches[2] ?? '');
  		if (isset($matches[1]) && $matches[1]) {
  			if ('!=' === $matches[1]) {
  				return $value !== $operand;
  			}

  			if ('^=' === $matches[1]) {
  				$operand = '/^.*' . preg_quote($operand) . '/';
  			} elseif ('$=' === $matches[1]) {
  				$operand = '/' . preg_quote($operand) . '.*$/';
  			} elseif ('*=' === $matches[1]) {
  				$operand = '/' . preg_quote($operand) . '/';
  			} elseif ('>' === $matches[1]) {
  				return $value > $operand;
  			} elseif ('>=' === $matches[1]) {
  				return $value >= $operand;
  			} elseif ('<' === $matches[1]) {
  				return $value < $operand;
  			} elseif ('<=' === $matches[1]) {
  				return $value <= $operand;
  			} elseif ('=' === $matches[1]) {
  				return $operand === $value;
  			}

  			return preg_match($operand, $value);
  		}

  		return $value === $operand;
  	}

  	/**
  	 * Replace the function tag.
  	 *
  	 * @param string $content A clip of block content
  	 *
  	 * @return string The block content which has replaced the function tag
  	 */
  	private function replaceFunc(string $content)
  	{
  		$result = '';
  		if (!$regex = RegexHelper::GetCache('template-replace-func')) {
  			$regex = new RegexHelper('/{(\w+)((?:\s+\w+(?:=(?:\$\w+(?:\.(?<content>\w+|(?<quote>[\'"])(?>(?!\k<quote>)[^\\\\\\\\]|\\\\.)*\k<quote>))*|\d+(?:\.\d+)?|(?P>content)))?)*)}/', 'template-replace-func');
  		}
  		while ($matches = $regex->match($content, $offset)) {
  			$func = $matches[1];

  			$result .= substr($content, 0, $offset[0]);

  			$content    = substr($content, $offset[0] + \strlen($matches[0]));
  			$parameters = $this->extractParams($matches[2]);

  			$structure = $this->block->getManager()->plugin('structure', $func);
  			if ($structure) {
  				$closingTag = '{/' . $matches[1] . '}';

  				if (false !== ($pos = strpos($content, $closingTag))) {
  					$wrapped = substr($content, 0, $pos);
  					$content = substr($content, $pos + \strlen($closingTag));
  				}
  				$result .= \call_user_func($structure, $wrapped, $parameters);
  			} else {
  				$function = $this->block->getManager()->plugin('function', $func);
  				$function = $function->bindTo($this);
  				if ($function) {
  					$result .= \call_user_func($function, $parameters);
  				}
  			}
  		}
  		$result .= $content;

  		return $result;
  	}

  	/**
  	 * Replace the parameter tag.
  	 *
  	 * @param string &$content A clip of block content
  	 *
  	 * @return string The block content which has replaced the parameter tag
  	 */
  	private function replaceTag(string $content, string $closing = '')
  	{
  		$content = $this->replaceFunc($content);
  		if (!$regex = RegexHelper::GetCache('template-replace-tag')) {
  			$regex = new RegexHelper('/{(!)?\$(\w+)((?:\[\d+\]|\.(?<content>\w+|(?<quote>[\'"])(?>(?!\k<quote>)[^\\\\\\\\]|\\\\.)*\k<quote>))*)(\|\w+(?::(?P>content))*)*(\s?(?:[<>]|(?:\s?[!*$^><])?=)\s?(?P>content))?(?:\s*#(\w+(?:\.\w+)*))?}/', 'template-replace-tag');
  		}

  		$result = '';
  		while ($matches = $regex->match($content, $offset)) {
  			// Get the parameter value
  			$value = $this->processParameter($matches[2], $matches[3] ?? '');

  			// Pass the value to modifier
  			$value = $this->processModifier($value, $matches[6] ?? '');

  			// If the comparison is given
  			if (isset($matches[7])) {
  				if (!is_scalar($value)) {
  					$value = false;
  				} else {
  					$value = $this->comparision($value, trim($matches[7]));
  				}
  			}

  			// Negative symbol is given
  			if (isset($matches[1]) && $matches[1]) {
  				$value = !$value;
  			}

  			$result .= substr($content, 0, $offset[0]);
  			$content = substr($content, $offset[0] + \strlen($matches[0]));

  			// If the bookmark is given, find the closing tag with the given bookmark name.
  			if (isset($matches[8])) {
  				$closingTag = '{/#' . $matches[8] . '}';
  			} else {
  				$closingTag = '{/$' . $matches[2] . ($matches[3] ?? '') . '}';
  			}

  			if (false !== ($pos = strpos($content, $closingTag))) {
  				// If the value is true, return the wrapped content as the value
  				if ($value) {
  					// Replace the parameter tag inside the wrapped content
  					$value = $this->replaceTag(substr($content, 0, $pos));
  				} else {
  					$value = null;
  				}

  				$content = substr($content, $pos + \strlen($closingTag));
  			}

  			if (is_scalar($value)) {
  				$result .= $value;
  			}
  		}
  		$result .= $content;

  		return $result;
  	}

  	/**
  	 * Extract the parameter key and value.
  	 *
  	 * @param string $parmString The parameter string
  	 *
  	 * @return array An array contains the parameter key and value
  	 */
  	private function extractParams(string $parmString)
  	{
  		if (!$regex = RegexHelper::GetCache('template-extract-params')) {
  			$regex = new RegexHelper('/\s+(\w+)(?:=(?:\$(\w+)((?:\.(?<content>\w+|(?<quote>[\'"])(?>(?!\k<quote>)[^\\\\\\\\]|\\\\.)*\k<quote>))*)|((\d+(?:\.\d+)?|(?P>content)))))?/', 'template-extract-params');
  		}

  		$result = [];
  		$regex->extract($parmString, function ($matches) use (&$result) {
  			$value = null;
  			if (isset($matches[2]) && $matches[2]) {
  				// Variable paramater
  				$value = $this->processParameter($matches[2], $matches[3] ?? '');
  			} else {
  				// Static paramater
  				$value = $this->unquote($matches[6]);
  			}

  			$result[$matches[1]] = $value;
  		});

  		return $result;
  	}

  	/**
  	 * Process the entities in list by the block name.
  	 *
  	 * @param string $blockName The block namne
  	 *
  	 * @return string All output content from entities
  	 */
  	private function processEntity(string $blockName)
  	{
  		$content = '';
  		if (isset($this->entities[$blockName])) {
  			foreach ($this->entities[$blockName] as $entity) {
  				$content .= $entity->process();
  			}

  			return $content;
  		}

  		return '';
  	}

  	/**
  	 * Unqoute the steing.
  	 *
  	 * @param string $value The string to be unquoted
  	 *
  	 * @return string The unquoted string
  	 */
  	private function unquote(string $value)
  	{
  		return preg_replace('/^([\'"]?)(.+)\1$/', '\2', $value);
  	}
  }
}
