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
  class DOMElement
  {
  	const DOMTYPE_TEXTNODE = 0;
  	const DOMTYPE_ELEMENT  = 1;
  	const DOMTYPE_COMMENT  = 2;
  	const DOMTYPE_DOCUMENT  = 3;

  	private $nodeType   = 0;
  	private $nodeName   = '';
  	private $text       = '';
  	private $attributes = [];
  	private $nodes      = [];

  	public function __construct($nodeType = DOMParser::DOMTYPE_TEXTNODE, $nodeName = '', $attrString = '')
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
  		$this->nodeName = $nodeName;
  		$this->nodeType = $nodeType;
  	}

  	public function html(string $html)
  	{
  		$html = trim($html);
  		if (!$html) {
  			new ThrowError('DOMElement', 1001, 'HTML content cannot be empty.');
  		}

  		$this->nodes = [];
  		$this->parse($html);

  		return $this;
  	}

  	public function append($node)
  	{
  		if ($node instanceof self) {
  			$this->nodes[] = $node;
  		} elseif (is_array($node)) {
  			foreach ($node as $domELement) {
  				$this->append($domELement);
  			}
  		} else {
  			new ThrowError('DOMElement', 2001, 'The object is not a valid DOMElement');
  		}

  		return $this;
  	}

  	public function getNodeType()
  	{
  		return $this->nodeType;
  	}

  	public function getNodeName()
  	{
  		return $this->nodeName;
  	}

  	public function setText(string $text)
  	{
  		$this->text = $text;

  		return $this;
  	}

  	public function getText()
  	{
  		return $this->text;
  	}

    public function getHTML()
    {
  
    }

  	public function getAttr(string $attrName)
  	{
  		$attrName = trim($attrName);
  		if (!$attrName || !array_key_exist($attrName, $this->attributes)) {
  			return null;
  		}

  		return $this->attributes[$attrName];
  	}

  	private function parse(string $html)
  	{
  		$unparsed = $html;
  		// Search the opening delimiter
  		while (preg_match('/(<!--(.*?)-->)|<\s*(!)?\s*([\w-]+)(\s+(?:[^<>\\\\]++|\\\\[<>])+)?>/', $unparsed, $matches, PREG_OFFSET_CAPTURE)) {
  			// Obtain previous text node
  			if ($matches[0][1] > 0) {
  				$textNode = new self(self::DOMTYPE_TEXTNODE);
  				$textNode->setText(substr($unparsed, 0, $matches[0][1]));
  				$this->nodes[] = $textNode;
  			}

  			$unparsed = substr($unparsed, $matches[0][1] + strlen($matches[0][0]));

  			if ($matches[2][0]) {
  				continue;
  			}

  			if ($matches[1][0]) {
  				// Comment
  				$commentNode = new self(self::DOMTYPE_COMMENT);
  				$commentNode->setText($matches[1][0]);
  				$this->nodes[] = $commentNode;
  			} else {
  				$nodeName    = $matches[4][0];
  				$elementNode = new self(self::DOMTYPE_ELEMENT, $nodeName, (isset($matches[5][0])) ? $matches[5][0] : '');
  				if (preg_match('/area|base|br|hr|embed|iframe|img|input|link|meta|param|source|track/i', $nodeName)) {
  					// Self-Closing Element
  				} else {
  					$parsed = $this->parseClosingTag($nodeName, $unparsed);
            if ($parsed[0]) {
              $elementNode->html($parsed[0]);
            }
            $unparsed = $parsed[1];
  				}
  				$this->nodes[] = $elementNode;
  			}
  		}

  		if ($unparsed) {
  			$textNode = new self(self::DOMTYPE_TEXTNODE);
  			$textNode->setText($unparsed);
  			$this->nodes[] = $textNode;
  		}

  		return $this;
  	}

  	private function parseClosingTag(string $nodeName, string $content)
  	{
  		$pos               = 0;
  		$balanceTagCount   = 1;
  		$lastClosingTagPos = 0;
  		$lastSplitPos      = 0;
  		while (preg_match('/(<\s*\/\s*' . $nodeName . '\s*>)|<\s*' . $nodeName . '(\s+(?:[^<>\\\\]++|\\\\[<>])+)?>/', $content, $matches, PREG_OFFSET_CAPTURE, $pos)) {
  			if ($matches[1][0]) {
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
  				$lastClosingTagPos = $matches[0][1];
  				$lastSplitPos      = $matches[0][1] + strlen($matches[0][0]);
  			} else {
  				++$balanceTagCount;
  			}

  			// Update the current position
  			$pos = $matches[0][1] + strlen($matches[0][0]);
  		}

  		new ThrowError('DOMElement', 2001, 'Missing ' . $nodeName . ' closing tag or it is not balanced.');
  	}
  }
}
