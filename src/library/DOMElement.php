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
  class DOMElement extends \ArrayObject
  {
  	public const DOMTYPE_TEXTNODE   = 0;
  	public const DOMTYPE_ELEMENT    = 1;
  	public const DOMTYPE_COMMENT    = 2;
  	public const DOMTYPE_NODELIST   = 3;
  	private const SELECTOR_REGEX    = '\s*((?<!\A)[>~+]|\A>?)\s*(?:(?:([\w-]++)?([.#]))?([\w-]++))?((?:\[[\w-]++(?:(?:[\^|$*!]?=)"(?:[^"\\\\]++|\\\\.)*")?\])*)(:(?:[\w-]++)(?:\((?:".+?"|[^\(\)]++)\))?)?';

  	private $nodeType     = 0;
  	private $nodeName     = '';
  	private $doctype      = [];
  	private $text         = '';
  	private $guid         = '';
  	private $attributes   = [];
  	private $elementNodes = [];
  	private $parent;

  	public function __construct(int $nodeType = DOMParser::DOMTYPE_TEXTNODE, string $nodeName = '', string $attrString = '', self $parent = null)
  	{
  		if (is_string($attrString)) {
  			$attrString = trim($attrString);
  			$attrSet    = preg_split('/(["\']).+?\1(*SKIP)(*FAIL)|\s+/', $attrString);
  			foreach ($attrSet as $attr) {
  				if (preg_match('/([\w-]++)(?:=([\'"]?)((?:[^\'"\\\\]++|\\\\.)*)\2)?/', $attr, $matches)) {
  					$this->attributes[$matches[1]] = (isset($matches[3])) ? $matches[3] : true;
  				}
  			}
  		}

  		$this->nodeName = strtolower($nodeName);
  		$this->nodeType = $nodeType;
  		$this->guid     = bin2hex(random_bytes(8));
  		$this->parent   = $parent;
  	}

  	public function __invoke(string $selector)
  	{
  		if (!preg_match('/(?:' . self::SELECTOR_REGEX . ')+/', $selector)) {
  			new ThrowError('DOMElement', 3001, 'Invalid selector syntax.');
  		}

  		$elements = [$this];
  		if (preg_match_all('/' . self::SELECTOR_REGEX . '/', $selector, $matches, PREG_SET_ORDER)) {
  			$collection = [];

  			foreach ($matches as $clip) {
  				if (!isset($clip[4]) && !$clip[5]) {
  					new ThrowError('DOMElement', 4001, 'Invalid Selector syntax');
  				}

  				$tagname   = '';
  				$attrValue = '';
  				$filter    = [];

  				if (isset($clip[6])) {
  					preg_match('/:([\w-]++)(?:\((?|"(.+?)"|([^\(\)]++))\))?/', $clip[6], $filter);
  				}

  				if (isset($clip[4])) {
  					if ($clip[2] || !$clip[3]) {
  						$tagname = ($clip[2]) ? $clip[2] : $clip[4];
  						if ($clip[2]) {
  							$attrValue = $clip[4];
  						}
  					} elseif ($clip[3]) {
  						$attrValue = $clip[4];
  					}
  				}

  				$attributes = [];
  				if ($clip[5]) {
  					if (preg_match_all('/\[([\w-]++)(?:([\^|$*!]?=)"((?:[^"\\\\]++|\\\\.)*)")?\]/', $clip[5], $delimiters, PREG_SET_ORDER)) {
  						foreach ($delimiters as $delim) {
  							$attributes[$delim[0]] = $delim;
  						}
  					}
  				}

  				foreach ($elements as $node) {
  					if ('>' === $clip[1] || !$clip[1]) {
  						$children = $node->children;
  						foreach ($children as $element) {
  							if ($this->matchElement($element, $tagname, $clip[3], $attrValue, $attributes, $filter)) {
  								$collection[$element->getGuid()] = $element;
  							}

  							if (!$clip[1]) {
  								$collection = array_merge($collection, $element($clip[0])->getArrayCopy());
  							}
  						}
  					} else {
  						$nodeList = $node->parent->children;
  						reset($nodeList);

  						$allowSibling = false;
  						do {
  							$siblingNode = current($nodeList);
  							if ($allowSibling) {
  								if ($this->matchElement($siblingNode, $tagname, $clip[3], $attrValue, $attributes, $filter)) {
  									$collection[$siblingNode->getGuid()] = $siblingNode;
  								}

  								if ('+' === $clip[1]) {
  									break;
  								}
  							} elseif (!$allowSibling && $node->getGuid() === $siblingNode->getGuid()) {
  								$allowSibling = true;
  							}
  						} while (false !== next($nodeList));
  					}
  				}

  				$elements   = $collection;
  				$collection = [];
  			}
  		}

  		$domNodeList = new self(self::DOMTYPE_NODELIST);

  		return $domNodeList->append($elements);
  	}

  	public function __get($name)
  	{
  		switch ($name) {
  			case 'innerText':
  				$text = '';
  				foreach ($this as $node) {
  					if (self::DOMTYPE_TEXTNODE === $node->nodeType) {
  						$text .= $node->getText();
  					} elseif (self::DOMTYPE_ELEMENT === $node->nodeType) {
  						$text .= $node->innerText;
  					}
  				}

  				return trim($text);
    		case 'innerHTML':
    		  if (self::DOMTYPE_TEXTNODE === $this->nodeType) {
    		  	return $this->text;
    		  }

    		  if (self::DOMTYPE_COMMENT === $this->nodeType) {
    		  	return '<!--' . $this->text . '-->';
    		  }

    		  if (self::DOMTYPE_ELEMENT === $this->nodeType) {
    		  	$html = '<' . $this->nodeName;
    		  	if ($this->attributes) {
    		  		foreach ($this->attributes as $name => $value) {
    		  			if (null !== $value) {
    		  				$html .= ' ' . $name;
    		  				if (true !== $value && $value) {
    		  					$html .= '="' . $value . '"';
    		  				}
    		  			}
    		  		}
    		  	}

    		  	if (preg_match('/area|base|br|hr|embed|iframe|img|input|link|meta|param|source|track/i', $this->nodeName)) {
    		  		$html .= ' />';
    		  	} else {
    		  		$html .= '>';
    		  		foreach ($this as $node) {
    		  			$html .= $node->innerHTML;
    		  		}
    		  		$html .= '</' . $this->nodeName . '>';
    		  	}
    		  }

    		  if (self::DOMTYPE_NODELIST === $this->nodeType) {
    		  	$html = '';
    		  	foreach ($this as $node) {
    		  		$html .= $node->innerHTML;
    		  	}
    		  }

    		  return $html;
    			case 'children':
    				return $this->elementNodes;
    			case 'nodeList':
    				return $this;
    			case 'parentNode':
    			  return $this->parent;
    			case 'nodeType':
    			  return $this->nodeType;
    			case 'nodeName':
    			  return $this->nodeName;
    	  }
  	}

  	public static function Parse(string $html)
  	{
  		$domElement = new self(self::DOMTYPE_NODELIST, '', '');

  		return $domElement->html($html);
  	}

  	public static function ParseFromFile(string $path)
  	{
  		return self::ParseFromURL($path);
  	}

  	public static function ParseFromURL(string $path)
  	{
  		$domElement = new self(self::DOMTYPE_NODELIST, '', '');

  		$html = file_get_contents($path);

  		return $domElement->html($html);
  	}

  	public function getGuid()
  	{
  		return $this->guid;
  	}

  	public function html(string $html)
  	{
  		$this->storage = [];
  		$html          = trim($html);
  		if ($html) {
  			$this->parseHTML($html);
  		}

  		return $this;
  	}

  	public function append($node)
  	{
  		if ($node instanceof self) {
  			$this[] = $node;
  		} elseif (is_array($node)) {
  			foreach ($node as $domELement) {
  				$this->append($domELement);
  			}
  		} else {
  			new ThrowError('DOMElement', 2001, 'The object is not a valid DOMElement');
  		}

  		return $this;
  	}

  	public function getText()
  	{
  		return $this->text;
  	}

  	public function setText(string $text)
  	{
  		$this->text = $text;

  		return $this;
  	}

  	public function removeAttr(string $attrName)
  	{
  		unset($this->attributes[$attrName]);

  		return $this;
  	}

  	public function hasAttr(string $attrName)
  	{
  		$attrName = trim($attrName);
  		if (!$attrName || !array_key_exists($attrName, $this->attributes)) {
  			return false;
  		}

  		return true;
  	}

  	public function getAttr(string $attrName)
  	{
  		$attrName = trim($attrName);
  		if (!$attrName || !array_key_exists($attrName, $this->attributes)) {
  			return null;
  		}

  		return $this->attributes[$attrName];
  	}

  	private function matchElement(self $domElement, string $tagname, string $classid, string $value, array $attrubites = [], array $filter = [])
  	{
  		if (($tagname && $domElement->nodeName === $tagname) || !$tagname) {
  			if ('.' === $classid) {
  				// Class
  				$classname = $domElement->getAttr('class');
  				if (!$classname || false === strpos(' ' . $classname . ' ', ' ' . $value . ' ')) {
  					return false;
  				}
  			} elseif ('#' === $classid) {
  				// ID
  				$id = $domElement->getAttr('id');
  				if (!$id || $id !== $value) {
  					return false;
  				}
  			}

  			if (count($attrubites)) {
  				foreach ($attrubites as $attr) {
  					if (isset($attr[2])) {
  						$value = $domElement->getAttr($attr[1]);
  						if ('=' === $attr[2] && $value !== $attr[3]) {
  							return false;
  						}

  						if ('!=' === $attr[2] && $value === $attr[3]) {
  							return false;
  						}

  						$pos = strpos($value, $attr[3]);
  						if ('*=' === $attr[2] && false === $pos) {
  							echo $pos;

  							return false;
  						}

  						if ('^=' === $attr[2] && 0 !== $pos) {
  							return false;
  						}

  						if ('$=' === $attr[2] && $pos + strlen($attr[3]) !== strlen($value)) {
  							return false;
  						}
  					} elseif (!$domElement->hasAttr($attr[1])) {
  						return false;
  					}
  				}
  			}

  			if (count($filter)) {
  				if (preg_match('/(first|last|nth)-child/i', $filter[1], $matches)) {
  					$nodeList = $domElement->parentNode->children;

  					if ('nth' === $matches[1]) {
  						if (!isset($filter[2]) || !preg_match('/(odd|even)|(\d*)n([+-]\d+)?|(\d+)/i', $filter[2], $matches)) {
  							return false;
  						}

  						$nodeList = array_values($nodeList);
  						if ($matches[1]) {
  							$multiplier = 2;
  							$offset     = ('even' === $matches[1]) ? 0 : 1;
  						} else {
  							$multiplier = (isset($matches[2]) && $matches[2]) ? $matches[2] : 1;
  							$offset     = (int) $matches[3] ?? 0;
  						}
  						$n = 0;

  						if (0 === $multiplier) {
  							return false;
  						}

  						$index = $multiplier * $n + $offset - 1;
  						while (abs($index) < count($nodeList)) {
  							if ($index >= 0 && $domElement->getGuid() === $nodeList[$index]->getGuid()) {
  								return true;
  							}

  							$index = $multiplier * ++$n + $offset - 1;
  						}

  						return false;
  					}
  					$node = ('last' === $matches[1]) ? end($nodeList) : reset($nodeList);
  					if ($domElement->getGuid() !== $node->getGuid()) {
  						return false;
  					}
  				}
  			}

  			return true;
  		}

  		return false;
  	}

  	private function parseHTML(string $html)
  	{
  		$unparsed = $html;
  		// Search the opening delimiter
  		while ($unparsed && preg_match('/(<!--(.*?)-->)|<\s*(!)?\s*([\w-]+)(\s+(?:[^<>\\\\]++|\\\\[<>])+)?>/', $unparsed, $matches, PREG_OFFSET_CAPTURE)) {
  			// Obtain previous text node
  			if ($matches[0][1] > 0) {
  				$textNode = new self(self::DOMTYPE_TEXTNODE, '', '', $this);
  				$textNode->setText(substr($unparsed, 0, $matches[0][1]));
  				$this[$textNode->getGuid()] = $textNode;
  			}

  			$unparsed = substr($unparsed, $matches[0][1] + strlen($matches[0][0]));

  			if (isset($matches[3][0]) && $matches[3][0]) {
  				continue;
  			}

  			if ($matches[1][0]) {
  				// Comment
  				$commentNode = new self(self::DOMTYPE_COMMENT, '', '', $this);
  				$commentNode->setText($matches[2][0]);
  				$this[$commentNode->getGuid()] = $commentNode;
  			} else {
  				$nodeName    = $matches[4][0];
  				$elementNode = new self(self::DOMTYPE_ELEMENT, $nodeName, (isset($matches[5][0])) ? $matches[5][0] : '', $this);
  				if (!preg_match('/area|base|br|hr|embed|iframe|img|input|link|meta|param|source|track/i', $nodeName)) {
  					$parsed = $this->parseClosingTag($nodeName, $unparsed);
  					if ($parsed[0]) {
  						$elementNode->html($parsed[0]);
  					}
  					$unparsed = $parsed[1];
  				}
  				$this[$elementNode->getGuid()]               = $elementNode;
  				$this->elementNodes[$elementNode->getGuid()] = $elementNode;
  			}
  		}

  		if ($unparsed) {
  			$textNode = new self(self::DOMTYPE_TEXTNODE, '', '', $this);
  			$textNode->setText($unparsed);
  			$this[$textNode->getGuid()] = $textNode;
  		}

  		return $this;
  	}

  	private function parseClosingTag(string $nodeName, string $content)
  	{
  		$pos             = 0;
  		$balanceTagCount = 1;
  		while (preg_match('/(<\s*\/\s*' . $nodeName . '\s*>)|<\s*' . $nodeName . '(\s+(?:[^<>\\\\]++|\\\\[<>])+)?>/', $content, $matches, PREG_OFFSET_CAPTURE, $pos)) {
  			if (isset($matches[1][0])) {
  				// If it is a close tag
  				--$balanceTagCount;

  				// If $balanceTagCount is 0, means it is the final close tag
  				if (0 === $balanceTagCount) {
  					// Pass wrapped content to parseModifier()
  					return [
  						substr($content, 0, $matches[0][1]),
  						substr($content, $matches[0][1] + strlen($matches[0][0])),
  					];
  				}
  			} else {
  				++$balanceTagCount;
  			}

  			// Update the current position
  			$pos = $matches[0][1] + strlen($matches[0][0]);
  		}

  		return [$content, ''];
  	}
  }
}
