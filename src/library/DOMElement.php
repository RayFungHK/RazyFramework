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
  	private $nodeType   = 0;
  	private $nodeName   = '';
  	private $text       = '';
  	private $attributes = [];

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

    public function getAttr(string $attrName)
    {
      $attrName = trim($attrName);
      if (!$attrName || !array_key_exist($attrName, $this->attributes)) {
        return null;
      }

      return $this->attributes[$attrName];
    }
  }
}
