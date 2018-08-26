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
  class TemplateBlock
  {
  	private $tagName      = '';
  	private $variableList = [];
  	private $structure;
  	private $blockList               = [];
  	private static $modifiers        = [];
  	private static $dynamicModifiers = [];

  	public function __construct($structure, $tagName)
  	{
  		$this->tagName = $tagName;
  		if ('RazyFramework\\TemplateStructure' !== get_class($structure)) {
  			new ThrowError('TemplateBlock', '1001', 'Invalid TemplateStructure object.');
  		}
  		$this->structure = $structure;
  	}

  	public function getBlockList($blockName)
  	{
  		return (isset($this->blockList[$blockName])) ? $this->blockList[$blockName] : [];
  	}

  	public function blockCount($blockName)
  	{
  		return (isset($this->blockList[$blockName])) ? count($this->blockList[$blockName]) : 0;
  	}

  	public function hasBlock($blockName, $tagName = '')
  	{
  		$blockName = trim($blockName);
  		if ($tagName) {
  			if (isset($this->blockList[$blockName][$tagName])) {
  				return true;
  			}
  		} else {
  			return $this->structure->getBlockStructure($blockName);
  		}
  	}

  	public function newBlock($blockName, $tagName = '')
  	{
  		$blockName = trim($blockName);
  		if ($this->structure->hasBlock($blockName)) {
  			$tagName = trim($tagName);
  			if (isset($this->blockList[$blockName][$tagName])) {
  				return $this->blockList[$blockName][$tagName];
  			}
  			$templateBlock = new self($this->structure->getBlockStructure($blockName), $tagName);
  			if ($tagName) {
  				$this->blockList[$blockName][$tagName] = $templateBlock;
  			} else {
  				$this->blockList[$blockName][] = $templateBlock;
  			}
  		} else {
  			new ThrowError('TemplateBlock', '2001', 'Block [' . $blockName . '] not found.');
  		}

  		return $templateBlock;
  	}

  	public function assign($variable, $value = null)
  	{
  		if (is_array($variable)) {
  			foreach ($variable as $tagName => $value) {
  				$this->assign($tagName, $value);
  			}
  		} else {
  			$this->variableList[$variable] = (is_callable($value)) ? call_user_func($value, $this->getVariable($variable)) : $value;
  		}

  		return $this;
  	}

  	public function output()
  	{
  		$seperatedBlock = [];
  		$outputContent  = '';

  		// Get the structure content
  		$structureContent = $this->structure->getStructureContent();

  		foreach ($structureContent as $content) {
  			// If current line isn't a string, TemplateStructure found
  			if (!is_string($content)) {
  				// Get TemplateStructure Block Name
  				$blockName = $content->getBlockName();

  				// Define a temporary name for post process
  				$tempName = '{__POSTPARSE#' . sprintf('%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff)) . '}';

  				// Put nest structure content into seperated list
  				$seperatedBlock[$tempName] = '';
  				if (isset($this->blockList[$blockName]) && count($this->blockList[$blockName])) {
  					foreach ($this->blockList[$blockName] as $block) {
  						$seperatedBlock[$tempName] .= $block->output();
  					}
  				}
  				$outputContent .= $tempName;
  			} else {
  				$outputContent .= $content;
  			}
  		}

  		// Search variable tag, pettern: {$variable_tag(|modifier(:parameter)*)*}
  		$outputContent = $this->parseTag($outputContent);

  		// Put back sub structure into output content
  		if (count($seperatedBlock)) {
  			$outputContent = str_replace(array_keys($seperatedBlock), array_values($seperatedBlock), $outputContent);
  		}

  		return $outputContent;
  	}

  	public function hasVariable($variable)
  	{
  		$variable = trim($variable);

  		return array_key_exists($variable, $this->variableList);
  	}

  	public function getVariable($variable)
  	{
  		$variable = trim($variable);

  		return (array_key_exists($variable, $this->variableList)) ? $this->variableList[$variable] : null;
  	}

  	public static function CreateModifier(string $name, callable $callback)
  	{
  		$name = trim($name);
  		if (preg_match('/^[\w-]+$/', $name)) {
  			$modifier = 'modifier.' . $name;
  			if (!isset(self::$modifiers[$modifier])) {
  				self::$dynamicModifiers[$modifier] = null;
  			}

  			if (is_callable($callback)) {
  				self::$dynamicModifiers[$modifier] = $callback;
  			}
  		}
  	}

  	public static function CreateFunctionTag(string $name, callable $callback)
  	{
  		$name = trim($name);
  		if (preg_match('/^[\w-]+$/', $name)) {
  			$modifier = 'func.' . $name;
  			if (!isset(self::$modifiers[$modifier])) {
  				self::$dynamicModifiers[$modifier] = null;
  			}

  			if (is_callable($callback)) {
  				self::$dynamicModifiers[$modifier] = $callback;
  			}
  		}
  	}

  	private function parseModifier($matches, $wrapped = null)
  	{
  		// If there is a $ at the beginning, parse as variable tag
  		if ($matches[1][0]) {
  			$tagname = $matches[2][0];
  			$value   = '';

  			// Search Modifier
  			$clipsCount = preg_match_all('/\|(\w+)((?::(?|(?:\w+)|"(?>[^"\\\\]+|\\\\.)*")?)*)/i', $matches[3][0], $clips, PREG_SET_ORDER);

  			// Find assigned value
  			if (array_key_exists($tagname, $this->variableList)) {
  				// Block level variable tag
  				$value = $this->variableList[$tagname];
  			} elseif ($this->structure->getManager()->hasGlobalVariable($tagname)) {
  				// Global level variable tag
  				$value = $this->structure->getManager()->getGlobalVariable($tagname);
  			} elseif (TemplateManager::HasEnvironmentVariable($tagname)) {
  				// Global level variable tag
  				$value = TemplateManager::GetEnvironmentVariable($tagname);
  			} elseif (0 === $clipsCount) {
  				return '';
  			}

  			// If variable tag includes modifier clips, start extract the modifier
  			if ($clipsCount) {
  				foreach ($clips as $clip) {
  					// Get the function name and parameters string
  					$funcname = $clip[1];

  					// Check the plugin is exists or not
  					if (self::GetModifier('modifier.' . $funcname)) {
  						$bindObject = (object) [
  							'arguments' => [],
  							'value'     => $value,
  						];

  						// Extract the parameters
  						if (isset($clip[2])) {
  							$clipsCount = preg_match_all('/:(?|(\w+)|(?:"((?>[^"\\\\]+|\\\\.)*)"))?/', $clip[2], $params, PREG_SET_ORDER);
  							foreach ($params as $match) {
  								if (isset($match[1])) {
  									if ('true' === $match[1]) {
  										$bindObject->arguments[] = true;
  									} elseif ('false' === $match[1]) {
  										$bindObject->arguments[] = false;
  									} else {
  										// If the parameter quoted by double quote, the string with backslashes
  										// that recognized by C-like \n, \r ..., octal and hexadecimal representation will be stripped off
  										$bindObject->arguments[] = stripcslashes($match[1]);
  									}
  								} else {
  									$bindObject->arguments[] = '';
  								}
  							}
  						}

  						// Execute the variable tag function
  						$value = $this->parseTag(self::CallModifier('modifier', $funcname, $bindObject));
  					}
  				}
  			}

  			// Balanced variable tag found, if return value is not false or null
  			// Return the wrapped content
  			if (null !== $wrapped) {
  				return ($value) ? $this->parseTag($wrapped) : '';
  			}

  			return $value;
  		}
  		$funcname   = $matches[2][0];
  		$clipsCount = preg_match_all('/\h+(\w+)(?:=(?|(\w+)|"((?>[^"\\\\]+|\\\\.)*)"))?/', $matches[3][0], $clips, PREG_SET_ORDER);

  		$bindObject = (object) [
  			'parameters' => [],
  			'content'    => null,
  		];

  		if (self::GetModifier('func.' . $funcname)) {
  			$parameters = [];
  			if (count($clips)) {
  				foreach ($clips as $clip) {
  					$value = true;
  					if (array_key_exists(2, $clip)) {
  						$value = stripcslashes($clip[2]);
  					}
  					$bindObject->parameters[$clip[1]] = $value;
  				}
  			}

  			$bindObject->content = $this->parseTag($wrapped);

  			// Execute the variable tag function
  			$result = self::CallModifier('func', $funcname, $bindObject);

  			return (false === $result) ? $matches[0][0] : $result;
  		}

  		return '';

  		return $matches[0][0];
  	}

  	private function parseClosingTag($matches, $outputContent)
  	{
  		// This procedure is guaranteed the variable tag or function tag is balanced
  		$matchedTag = $matches;

  		if ($matches[1][0]) {
  			// Variable tag
  			$tagName = preg_quote($matches[1][0]) . '(' . $matches[2][0] . ')';
  			$regex   = '/\{(?:\/' . $tagName . '|(' . $tagName . '((?:\|\w+(?::(?>\w+|"(?>[^"\\\\]+|\\\\.)*")?)*)*)))\}/';
  		} else {
  			// Function tag
  			$regex = '/\{(?:\/(' . $matches[2][0] . ')|(' . $matches[2][0] . '((?:\h+\w+(?:=(?>\w+|"(?>[^"\\\\]+|\\\\.)*"))*)*)))\}/';
  		}

  		$pos               = 0;
  		$balanceTagCount   = 1;
  		$lastClosingTagPos = 0;
  		$lastSplitPos      = 0;
  		// Search the tag with the same tag name and type
  		while (preg_match($regex, $outputContent, $matches, PREG_OFFSET_CAPTURE, $pos)) {
  			if ($matches[1][0]) {
  				// If it is a close tag
  				--$balanceTagCount;

  				// If $balanceTagCount is 0, means it is the final close tag
  				if (0 === $balanceTagCount) {
  					// Pass wrapped content to parseModifier()
  					return [
  						$this->parseModifier($matchedTag, substr($outputContent, 0, $matches[0][1])),
  						substr($outputContent, $matches[0][1] + strlen($matches[0][0])),
  					];
  				}
  				$lastClosingTagPos = $matches[0][1];
  				$lastSplitPos      = $matches[0][1] + strlen($matches[0][0]);
  			} else {
  				++$balanceTagCount;
  			}

  			// Update the current position
  			$pos = $matches[0][1] + strlen($matches[0][0]);
  		}

  		if ($balanceTagCount > 0 && $lastClosingTagPos) {
  			// If the variable tag is not balanced and there is atleast one set variable tag found
  			// Pass wrapped content to parseModifier() with the last closing tag position
  			return [
  				$this->parseModifier($matchedTag, substr($outputContent, 0, $lastClosingTagPos)),
  				substr($outputContent, $lastSplitPos),
  			];
  		}

  		return [$this->parseModifier($matchedTag, null), $outputContent];
  	}

  	private function parseTag($outputContent)
  	{
  		$result   = '';
  		$unparsed = $outputContent;
  		// Search variable tag or function tag
  		while (preg_match('/\{(?|(?:(\$)(\w+)((?:\|\w+(?::(?>\w+|"(?>[^"\\\\]+|\\\\.)*")?)*)*))|(?:()(\w+)((?:\h+\w+(?:=(?>\w+|"(?>[^"\\\\]+|\\\\.)*"))*)*)))\}/s', $unparsed, $matches, PREG_OFFSET_CAPTURE)) {
  			// Put the string to result that before the variable tag or function tag
  			$result .= substr($unparsed, 0, $matches[0][1]);

  			// Find the close tag and parse the content
  			$parsed = $this->parseClosingTag($matches, substr($unparsed, $matches[0][1] + strlen($matches[0][0])));

  			// Put the parsed variable tag or function tag to result
  			$result .= $parsed[0];

  			// Reset the unparsed content
  			$unparsed = $parsed[1];
  		}

  		// Put the unparsed content to result
  		$result .= $unparsed;

  		return $result;
  	}

  	private static function GetModifier($modifier)
  	{
  		if (!array_key_exists($modifier, self::$modifiers)) {
  			self::$modifiers[$modifier] = null;

  			$pluginFile = __DIR__ . \DIRECTORY_SEPARATOR . 'tpl_plugins' . \DIRECTORY_SEPARATOR . $modifier . '.php';

  			if (file_exists($pluginFile)) {
  				$callback = require $pluginFile;
  				if (is_callable($callback)) {
  					self::$modifiers[$modifier] = $callback;

  					return $callback;
  				}
  			}
  		}

  		if (array_key_exists($modifier, self::$dynamicModifiers)) {
  			return self::$dynamicModifiers[$modifier];
  		}

  		return self::$modifiers[$modifier];
  	}

  	private static function CallModifier($type, $modifier, object $bindObject)
  	{
  		$modifierName = $type . '.' . $modifier;
  		if (!($modifier = self::GetModifier($modifierName))) {
  			new ThrowError('TemplateBlock', '3001', 'Cannot load [' . $modifierName . '] modifier function.');
  		}

  		return call_user_func_array(
			\Closure::bind($modifier, $bindObject),
			(property_exists($bindObject, 'arguments')) ? $bindObject->arguments : []
	  );
  	}
  }
}
